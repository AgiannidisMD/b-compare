<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\MedicalCondition;
use App\Models\Supplement;
use App\Models\SupplementCategory;
use App\Models\SupplementConditionMapping;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    private AIService $ai;

    public function __construct(AIService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Get evidence-based knowledge for the current context
     */
    private function getContextualKnowledge($conversation): string
    {
        $conditions = $conversation->extracted_conditions ?? [];
        $category = $conversation->selected_category_id
            ? SupplementCategory::find($conversation->selected_category_id)?->name
            : null;

        if (empty($conditions) && !$category) {
            return '';
        }

        $knowledge = [];

        // Get condition-specific knowledge
        $mappings = SupplementKnowledgeBase::getConditionMappings();
        foreach ($conditions as $condition) {
            foreach ($mappings as $condName => $data) {
                if (stripos($condName, $condition) !== false || stripos($condition, $condName) !== false) {
                    if (!empty($data['recommended'])) {
                        $knowledge[] = "## Για {$condName}:";
                        foreach ($data['recommended'] as $supp => $info) {
                            if ($category && stripos($supp, $category) !== false) {
                                // Detailed info for selected category
                                $knowledge[] = "- **{$supp}**: {$info['mechanism']}";
                                if (!empty($info['dosage'])) {
                                    $knowledge[] = "  Δοσολογία: {$info['dosage']}";
                                }
                                if (!empty($info['evidence'])) {
                                    $knowledge[] = "  Επίπεδο τεκμηρίωσης: {$info['evidence']}";
                                }
                                if (!empty($info['forms'])) {
                                    $formScores = [];
                                    foreach ($info['forms'] as $form => $score) {
                                        $formScores[] = "{$form}({$score}/10)";
                                    }
                                    $knowledge[] = "  Καλύτερες μορφές: " . implode(', ', $formScores);
                                }
                            } else {
                                // Brief mention for other categories
                                $evidence = $info['evidence'] ?? 'moderate';
                                $knowledge[] = "- {$supp} (τεκμηρίωση: {$evidence})";
                            }
                        }
                    }
                    if (!empty($data['caution'])) {
                        foreach ($data['caution'] as $supp => $warning) {
                            $knowledge[] = "⚠️ **Προσοχή** - {$supp}: {$warning}";
                        }
                    }
                    break;
                }
            }
        }

        // Get bioavailability data for selected category
        if ($category) {
            $bioData = SupplementKnowledgeBase::getBioavailabilityData();
            foreach ($bioData as $cat => $forms) {
                if (stripos($cat, $category) !== false || stripos($category, $cat) !== false) {
                    $knowledge[] = "\n## Βιοδιαθεσιμότητα {$cat}:";
                    foreach ($forms as $form => $info) {
                        $score = $info['score'];
                        $notes = $info['notes'];
                        $absorption = $info['absorption'] ?? '';
                        $absText = $absorption ? " ({$absorption})" : '';
                        $knowledge[] = "- {$form}: {$score}/10{$absText} - {$notes}";
                    }
                    break;
                }
            }
        }

        if (empty($knowledge)) {
            return '';
        }

        return "\n# ΕΠΙΣΤΗΜΟΝΙΚΗ ΓΝΩΣΗ\n" . implode("\n", $knowledge) . "\n";
    }

    /**
     * Build a full clinical prompt for the streaming endpoint.
     * Same intelligence as the extraction prompt but outputs plain text (no JSON).
     */
    public function buildStreamingPrompt(ChatConversation $conversation, string $userMessage): string
    {
        $categories = SupplementCategory::pluck('name')->implode(', ');
        $conditions = MedicalCondition::pluck('name')->implode(', ');

        $currentCategory = $conversation->selected_category_id
            ? SupplementCategory::find($conversation->selected_category_id)?->name
            : null;
        $categorySlug = $conversation->selected_category_id
            ? SupplementCategory::find($conversation->selected_category_id)?->slug
            : null;

        $clinicalKnowledge = $this->buildClinicalKnowledge($categorySlug);
        $interactionWarnings = $this->buildInteractionWarnings($categorySlug);
        $liveProducts = $this->buildLiveProductContext($conversation->selected_category_id, $userMessage);
        $comparisonContext = $this->buildComparisonContext($userMessage, $conversation->selected_category_id);

        $userProfile = UserProfile::getOrCreateForSession($conversation->session_id, $conversation->user_ip ?? '');
        $memoryContext = $userProfile->getMemoryContext();

        return <<<PROMPT
Είσαι κλινικός σύμβουλος συμπληρωμάτων. Μιλάς Ελληνικά. Απαντάς σε ΚΑΘΑΡΟ ΚΕΙΜΕΝΟ χωρίς markdown, χωρίς **, χωρίς bullets.

Κατηγορίες: {$categories}
Καταστάσεις: {$conditions}
Επιλεγμένη κατηγορία: {$currentCategory}
{$memoryContext}
{$clinicalKnowledge}
{$interactionWarnings}
{$liveProducts}
{$comparisonContext}

ΚΑΝΟΝΕΣ:
- Χρησιμοποίησε τα ΠΡΑΓΜΑΤΙΚΑ ΔΕΔΟΜΕΝΑ από τη βάση αν υπάρχουν παραπάνω
- Αναφέρου σε συγκεκριμένα προϊόντα με brand + title + score
- Εξήγησε μορφές συστατικών (glycinate vs oxide) και δόσεις
- Προειδοποίησε για αλληλεπιδράσεις αν ο χρήστης αναφέρει φάρμακα/συμπληρώματα
- ΜΕΓΙΣΤΟ 2-3 προτάσεις. Σύντομα και περιεκτικά. Μην επαναλαμβάνεις πληροφορίες
- Κάνε ΜΙΑ ερώτηση τη φορά
- ΠΟΤΕ markdown, bullets, αστερίσκους
PROMPT;
    }

    public function processMessage(ChatConversation $conversation, string $userMessage): array
    {
        $history = $conversation->conversation_history ?? [];
        $history[] = ['role' => 'user', 'content' => $userMessage];

        // Get or create user profile for memory
        $userProfile = UserProfile::getOrCreateForSession($conversation->session_id, $conversation->user_ip);
        $userProfile->recordActivity();

        // Flow 1: Extract parameters
        $extracted = $this->extractParameters($history, $conversation, $userProfile);

        // Update conversation
        $conversation->update([
            'extracted_conditions' => $extracted['conditions'],
            'extracted_preferences' => $extracted['preferences'],
            'selected_category_id' => $extracted['category_id'],
            'confidence_score' => $extracted['confidence'],
            'is_ready_to_recommend' => $extracted['is_ready'],
            'conversation_history' => array_merge($history, [
                ['role' => 'assistant', 'content' => $extracted['response']]
            ])
        ]);

        // Update user profile with new knowledge
        if (!empty($extracted['conditions'])) {
            $userProfile->addConditions($extracted['conditions']);
        }
        if (!empty($extracted['preferences']['allergies'])) {
            $userProfile->addAllergies($extracted['preferences']['allergies']);
        }
        if (!empty($extracted['preferences']['dietary'])) {
            $userProfile->addDietaryPreferences($extracted['preferences']['dietary']);
        }
        if (!empty($extracted['preferences']['budget'])) {
            $userProfile->update(['budget_preference' => $extracted['preferences']['budget']]);
        }
        if ($extracted['category_id']) {
            $userProfile->trackCategoryExplored($extracted['category_id']);
        }

        // If ready, generate recommendations
        if ($extracted['is_ready']) {
            $recommendations = $this->generateRecommendations($conversation, $userProfile);

            // Track recommended supplements
            foreach ($recommendations['recommendations'] ?? [] as $rec) {
                if (isset($rec['id'])) {
                    $userProfile->trackSupplementRecommended($rec['id']);
                }
            }

            return [
                'message' => $extracted['response'],
                'recommendations' => $recommendations
            ];
        }

        return [
            'message' => $extracted['response'],
            'recommendations' => null
        ];
    }

    /**
     * Build the system prompt for parameter extraction
     */
    private function buildExtractionSystemPrompt($conversation, ?UserProfile $userProfile = null, string $userMessage = ''): string
    {
        $categories = SupplementCategory::pluck('name')->implode(', ');
        $conditions = MedicalCondition::pluck('name')->implode(', ');

        $currentCategory = $conversation->selected_category_id
            ? SupplementCategory::find($conversation->selected_category_id)?->name
            : null;
        $currentConditions = $conversation->extracted_conditions ?? [];
        $conditionsText = !empty($currentConditions) ? implode(', ', $currentConditions) : 'Καμία';

        // Get evidence-based knowledge for current context
        $contextualKnowledge = $this->getContextualKnowledge($conversation);

        // Get clinical knowledge from clinical_doses.php
        $categorySlug = $conversation->selected_category_id
            ? SupplementCategory::find($conversation->selected_category_id)?->slug
            : null;
        $clinicalKnowledge = $this->buildClinicalKnowledge($categorySlug);
        $interactionWarnings = $this->buildInteractionWarnings($categorySlug);
        $liveProducts = $this->buildLiveProductContext($conversation->selected_category_id, $userMessage);
        $comparisonContext = $this->buildComparisonContext($userMessage, $conversation->selected_category_id);

        // Get user memory context
        $memoryContext = $userProfile ? $userProfile->getMemoryContext() : '';

        return <<<PROMPT
# ΡΟΛΟΣ

Είσαι κλινικός σύμβουλος συμπληρωμάτων διατροφής. Μιλάς Ελληνικά. Έχεις πρόσβαση σε βάση δεδομένων 23,000+ συμπληρωμάτων με κλινική ανάλυση ποιότητας.

Ο στόχος σου ΔΕΝ είναι απλά να πουλήσεις ένα προϊόν — είναι να βοηθήσεις τον χρήστη να κατανοήσει ΤΙ χρειάζεται και ΓΙΑΤΙ, και μετά να του δώσεις τις καλύτερες επιλογές.

# ΠΛΗΡΟΦΟΡΙΕΣ ΚΑΤΗΓΟΡΙΩΝ
{$categories}

# ΙΑΤΡΙΚΕΣ ΚΑΤΑΣΤΑΣΕΙΣ
{$conditions}

# ΤΡΕΧΟΥΣΑ ΣΥΝΟΜΙΛΙΑ
- Κατηγορία: {$currentCategory}
- Καταστάσεις: {$conditionsText}
{$memoryContext}
{$clinicalKnowledge}
{$interactionWarnings}
{$liveProducts}
{$comparisonContext}
{$contextualKnowledge}

# ΠΩΣ ΝΑ ΑΠΑΝΤΑΣ

## ΣΗΜΑΝΤΙΚΟ: Χρησιμοποίησε τα ΠΡΑΓΜΑΤΙΚΑ ΔΕΔΟΜΕΝΑ
Αν υπάρχουν δεδομένα από τη βάση παραπάνω, ΧΡΗΣΙΜΟΠΟΙΗΣΕ ΤΑ στην απάντησή σου.
- Αναφέρου σε συγκεκριμένα προϊόντα με brand + title
- Αναφέρου σε πραγματικά scores
- Αν ο χρήστης ρωτήσει "τι έχετε σε X" → απάντησε με πραγματικά προϊόντα
- Αν ρωτήσει για μάρκα → δώσε πραγματικά στοιχεία (trust score, products)
- ΜΗΝ λες "έχουμε πολλές επιλογές" — πες "έχουμε 47 glycinate, τα top 3 είναι..."

## Αν ο χρήστης ρωτήσει για συγκεκριμένο συστατικό (π.χ. "τι μαγνήσιο να πάρω"):
- ΕΞΗΓΗΣΕ τη διαφορά μεταξύ μορφών (glycinate vs oxide vs citrate) χρησιμοποιώντας τα clinical data παραπάνω
- Πες ΤΙ ΔΟΣΗ χρειάζεται (θεραπευτικό εύρος)
- Πες ΤΙ ΜΟΡΦΗ να αποφύγει και ΓΙΑΤΙ
- Ρώτα για ΤΙ ΣΚΟΠΟ το θέλει (ύπνο, μύες, άγχος) γιατί αλλάζει η σύσταση

## Αν ο χρήστης αναφέρει κατάσταση υγείας (π.χ. "έχω PCOS"):
- Πρότεινε τα κατάλληλα συστατικά με ΤΕΚΜΗΡΙΩΣΗ
- Εξήγησε τον μηχανισμό δράσης σε απλά λόγια
- Ρώτα αν παίρνει ήδη κάτι ή αν έχει αντενδείξεις

## Αν ο χρήστης ρωτήσει "ποιο είναι το καλύτερο X":
- ΜΗΝ λες απλά "το #1 στην κατηγορία"
- ΕΞΗΓΗΣΕ γιατί: "Η μορφή glycinate απορροφάται 80%+ ενώ η oxide μόνο 4%. Γι' αυτό ένα φθηνότερο glycinate είναι πολύ καλύτερο από ένα ακριβό oxide."

## Αν ο χρήστης αναφέρει ότι παίρνει ήδη φάρμακα ή άλλα συμπληρώματα:
- ΕΛΕΓΞΕ αλληλεπιδράσεις (calcium+iron, zinc+copper, omega-3+αντιπηκτικά κλπ)
- ΠΡΟΕΙΔΟΠΟΙΗΣΕ αν υπάρχει κίνδυνος
- Πρότεινε σωστό timing (π.χ. "πάρτε τον σίδηρο 2 ώρες μακριά από το ασβέστιο")

## Αν ο χρήστης ρωτήσει για δοσολογία:
- Δώσε τα θεραπευτικά εύρη από τα clinical data (π.χ. "200-400mg μαγνήσιο")
- ΠΑΝΤΑ πρόσθεσε "σε συνεννόηση με τον γιατρό σας για την ακριβή δοσολογία"

## Γενικοί κανόνες:
- Κάνε ΜΙΑ ερώτηση τη φορά
- ΜΕΓΙΣΤΟ 2-3 σύντομες προτάσεις. Πάντα σύντομα, σαν μήνυμα σε chat
- Να είσαι ειλικρινής — αν ένα δημοφιλές προϊόν είναι κακής ποιότητας, πες το
- ΠΟΤΕ μην δίνεις ιατρικές διαγνώσεις
- Αν ρωτήσει εκτός θέματος → "Είμαι εξειδικευμένος στα συμπληρώματα. Πώς μπορώ να σας βοηθήσω;"

## ΜΟΡΦΟΠΟΙΗΣΗ ΑΠΑΝΤΗΣΗΣ — ΠΟΛΥ ΣΗΜΑΝΤΙΚΟ
- ΠΟΤΕ μη χρησιμοποιείς markdown (**, *, •, #, -, ```)
- ΠΟΤΕ bullet points ή αρίθμηση
- Γράψε σε φυσικές, συνεχόμενες προτάσεις σαν να μιλάς σε έναν ασθενή
- Αν θέλεις να αναφέρεις πολλές επιλογές, βάλτες σε πρόταση: "Οι 3 καλύτερες επιλογές είναι η Thorne (8.9/10), η NOW Foods (8.4/10) και η Doctor's Best (8.1/10)."
- Αν θέλεις να δώσεις δόσεις, γράψε: "Η θεραπευτική δόση είναι 200-400mg ημερησίως."
- ΟΧΙ λίστες, ΟΧΙ πίνακες, ΟΧΙ formatting. Μόνο καθαρό κείμενο.

# ΠΑΡΑΔΕΙΓΜΑ ΣΩΣΤΗΣ ΑΠΑΝΤΗΣΗΣ

Χρήστης: "Θέλω μαγνήσιο για ύπνο"
Απάντηση: "Για ύπνο πάρτε glycinate 200-400mg πριν κοιμηθείτε. Αποφύγετε το oxide, απορροφάται ελάχιστα. Παίρνετε κάτι άλλο;"

Χρήστης: "Τι ινοσιτόλη έχετε;"
Απάντηση: "Έχουμε 43 προϊόντα. Τα top 3 είναι Fairhaven Health (8.2/10), Wholesome Story (7.9/10) και NOW Foods (7.5/10). Προτιμήστε σκόνη myo-inositol σε αναλογία 40:1 με d-chiro. Για ποιο σκοπό τη θέλετε;"

# FORMAT ΑΠΑΝΤΗΣΗΣ

Απάντησε ΜΟΝΟ με valid JSON:
{
  "response": "Η ελληνική απάντησή σου εδώ",
  "category": "όνομα κατηγορίας ή null",
  "conditions": ["κατάσταση1", "κατάσταση2"],
  "budget": null,
  "allergies": [],
  "dietary_preferences": [],
  "confidence": 0.5,
  "is_ready": false
}

**is_ready = true** ΜΟΝΟ όταν:
- Έχεις κατηγορία ΚΑΙ
- Έχεις τουλάχιστον 1 ιατρική κατάσταση/στόχο

PROMPT;
    }

    private function extractParameters($history, $conversation, ?UserProfile $userProfile = null): array
    {
        // Get the latest user message for live product context
        $lastUserMessage = '';
        for ($i = count($history) - 1; $i >= 0; $i--) {
            if (($history[$i]['role'] ?? '') === 'user') {
                $lastUserMessage = $history[$i]['content'] ?? '';
                break;
            }
        }

        $systemPrompt = $this->buildExtractionSystemPrompt($conversation, $userProfile, $lastUserMessage);

        // Call AI service
        $result = $this->ai->chat($systemPrompt, $history, 'chat');

        if ($result['success'] && !empty($result['content'])) {
            $text = $result['content'];

            // Clean up response - remove markdown if present
            $text = preg_replace('/```json\s*/i', '', $text);
            $text = preg_replace('/```\s*/', '', $text);
            $text = trim($text);

            $data = json_decode($text, true);

            if ($data && isset($data['response'])) {
                $categoryId = null;

                if (!empty($data['category'])) {
                    $cat = SupplementCategory::where('name', 'LIKE', '%' . $data['category'] . '%')->first();
                    $categoryId = $cat?->id ?? $conversation->selected_category_id;
                }

                // Merge with existing conditions
                $existingConditions = $conversation->extracted_conditions ?? [];
                $newConditions = $data['conditions'] ?? [];
                $mergedConditions = array_unique(array_merge($existingConditions, $newConditions));

                return [
                    'response' => $data['response'],
                    'category_id' => $categoryId ?? $conversation->selected_category_id,
                    'conditions' => $mergedConditions,
                    'preferences' => [
                        'budget' => $data['budget'] ?? null,
                        'allergies' => $data['allergies'] ?? [],
                        'dietary' => $data['dietary_preferences'] ?? [],
                    ],
                    'confidence' => $data['confidence'] ?? 0.5,
                    'is_ready' => $data['is_ready'] ?? false
                ];
            }

            // JSON parsing failed — try to extract response text anyway
            // The AI might have returned plain text or malformed JSON
            Log::warning('AI extraction returned invalid JSON, using raw text', ['content' => mb_substr($text, 0, 200)]);

            // Try to extract "response" field from malformed JSON
            $responseText = $text;
            if (preg_match('/"response"\s*:\s*"(.*?)(?:"|$)/s', $text, $matches)) {
                $responseText = $matches[1];
            }
            // Strip any remaining JSON artifacts
            $responseText = preg_replace('/[{}"\[\]]/', '', $responseText);
            $responseText = preg_replace('/^\s*(response|category|conditions|budget|allergies|dietary_preferences|confidence|is_ready)\s*:?\s*/mi', '', $responseText);
            $responseText = trim($responseText);

            if (mb_strlen($responseText) > 10) {
                return [
                    'response' => $responseText,
                    'category_id' => $conversation->selected_category_id,
                    'conditions' => $conversation->extracted_conditions ?? [],
                    'preferences' => $conversation->extracted_preferences ?? [],
                    'confidence' => 0.3,
                    'is_ready' => false
                ];
            }
        } else {
            Log::error('AI extraction failed', ['error' => $result['error'] ?? 'Unknown']);
        }

        // Absolute fallback
        return [
            'response' => $this->getFallbackResponse($conversation),
            'category_id' => $conversation->selected_category_id,
            'conditions' => $conversation->extracted_conditions ?? [],
            'preferences' => $conversation->extracted_preferences ?? [],
            'confidence' => 0.3,
            'is_ready' => false
        ];
    }

    /**
     * Get contextual fallback response
     */
    private function getFallbackResponse($conversation): string
    {
        if (!$conversation->selected_category_id) {
            return 'Καλησπέρα! Είμαι ο SupplementIQ και θα σας βοηθήσω να βρείτε τα καλύτερα συμπληρώματα. Ποια κατηγορία σας ενδιαφέρει; (π.χ. μαγνήσιο, ωμέγα-3, βιταμίνη D, προβιοτικά)';
        }

        if (empty($conversation->extracted_conditions)) {
            $category = SupplementCategory::find($conversation->selected_category_id);
            return "Τέλεια επιλογή το {$category->name}! Για ποιο λόγο ενδιαφέρεστε γι' αυτό το συμπλήρωμα; (π.χ. συγκεκριμένη κατάσταση υγείας, γενική ευεξία, αθλητική απόδοση)";
        }

        return 'Ευχαριστώ! Έχετε κάποια αλλεργία ή διατροφικούς περιορισμούς που πρέπει να λάβω υπόψη;';
    }

    /**
     * Build evidence-based knowledge section for recommendations
     */
    private function buildEvidenceSection(string $categoryName, array $conditions): string
    {
        $sections = [];

        // Get condition-specific evidence
        $mappings = SupplementKnowledgeBase::getConditionMappings();
        foreach ($conditions as $condition) {
            foreach ($mappings as $condName => $data) {
                if (stripos($condName, $condition) !== false || stripos($condition, $condName) !== false) {
                    foreach ($data['recommended'] as $supp => $info) {
                        if (stripos($supp, $categoryName) !== false || stripos($categoryName, $supp) !== false) {
                            $sections[] = "## Για {$condName} + {$supp}:";
                            $sections[] = "- Μηχανισμός: {$info['mechanism']}";
                            $sections[] = "- Επίπεδο τεκμηρίωσης: {$info['evidence']}";
                            if (!empty($info['dosage'])) {
                                $sections[] = "- Θεραπευτική δοσολογία: {$info['dosage']}";
                            }
                            if (!empty($info['studies'])) {
                                $sections[] = "- Μελέτες: {$info['studies']}";
                            }
                            if (!empty($info['forms'])) {
                                $sections[] = "- Προτιμώμενες μορφές (από καλύτερη σε χειρότερη):";
                                arsort($info['forms']);
                                foreach ($info['forms'] as $form => $score) {
                                    $sections[] = "  * {$form}: {$score}/10";
                                }
                            }
                        }
                    }
                    // Add cautions
                    if (!empty($data['caution'])) {
                        foreach ($data['caution'] as $supp => $warning) {
                            if (stripos($supp, $categoryName) !== false || stripos($categoryName, $supp) !== false) {
                                $sections[] = "⚠️ ΠΡΟΣΟΧΗ: {$warning}";
                            }
                        }
                    }
                    break;
                }
            }
        }

        // Get bioavailability data for the category
        $bioData = SupplementKnowledgeBase::getBioavailabilityData();
        foreach ($bioData as $cat => $forms) {
            if (stripos($cat, $categoryName) !== false || stripos($categoryName, $cat) !== false) {
                $sections[] = "\n## Βιοδιαθεσιμότητα μορφών {$cat}:";
                // Sort by score descending
                uasort($forms, fn($a, $b) => $b['score'] - $a['score']);
                foreach ($forms as $form => $info) {
                    $absorption = isset($info['absorption']) ? " ({$info['absorption']} απορρόφηση)" : '';
                    $sections[] = "- **{$form}**: {$info['score']}/10{$absorption}";
                    $sections[] = "  {$info['notes']}";
                }
                break;
            }
        }

        if (empty($sections)) {
            return '';
        }

        return "\n# ΚΛΙΝΙΚΗ ΤΕΚΜΗΡΙΩΣΗ\n" . implode("\n", $sections) . "\n";
    }

    /**
     * Query database for real product data relevant to current conversation.
     * Gives the AI actual product info to reference during chat (not just at recommendation time).
     */
    private function buildLiveProductContext(?int $categoryId, string $userMessage): string
    {
        if (!$categoryId) return '';

        $category = SupplementCategory::find($categoryId);
        if (!$category) return '';

        $sections = ["\n# ΠΡΑΓΜΑΤΙΚΑ ΔΕΔΟΜΕΝΑ ΑΠΟ ΤΗ ΒΑΣΗ\n"];
        $sections[] = "Κατηγορία: {$category->name} ({$category->product_count} προϊόντα)\n";

        // Top 5 in category by overall score
        $top5 = Supplement::where('category_id', $categoryId)
            ->whereNotNull('overall_recommendation_score')
            ->orderByDesc('overall_recommendation_score')
            ->limit(5)
            ->get();

        if ($top5->isNotEmpty()) {
            $sections[] = "## Top 5 στην κατηγορία:";
            foreach ($top5 as $i => $s) {
                $rank = $s->category_rank ?? ($i + 1);
                $flags = !empty($s->red_flags_detected) ? ' [' . count($s->red_flags_detected) . ' flags]' : '';
                $sections[] = "#{$rank} {$s->brand} - {$s->title} | Score: {$s->overall_recommendation_score} | Dose: {$s->clinical_dose_score} | Bio: {$s->bioavailability_score}{$flags}";
            }
        }

        // Search for products matching user's message keywords
        $searchTerms = $this->extractSearchTerms($userMessage);
        if (!empty($searchTerms)) {
            $query = Supplement::where('category_id', $categoryId);
            foreach ($searchTerms as $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'LIKE', "%{$term}%")
                      ->orWhere('brand', 'LIKE', "%{$term}%");
                });
            }
            $matched = $query->orderByDesc('overall_recommendation_score')->limit(5)->get();

            if ($matched->isNotEmpty()) {
                $sections[] = "\n## Προϊόντα που ταιριάζουν στην ερώτηση:";
                foreach ($matched as $s) {
                    $ingredients = '';
                    if (!empty($s->active_ingredients) && is_array($s->active_ingredients)) {
                        $ingList = array_map(fn($ing) => ($ing['name'] ?? '') . ' ' . ($ing['amount'] ?? '') . ($ing['unit'] ?? ''), array_slice($s->active_ingredients, 0, 3));
                        $ingredients = ' | Συστατικά: ' . implode(', ', $ingList);
                    }
                    $flags = !empty($s->red_flags_detected) ? ' | RED FLAGS: ' . implode(', ', $s->red_flags_detected) : '';
                    $sections[] = "- {$s->brand} - {$s->title} | Score: {$s->overall_recommendation_score} | Dose: {$s->clinical_dose_score} | Bio: {$s->bioavailability_score} | Form: {$s->dosage_form}{$ingredients}{$flags}";
                }
            }
        }

        // Brand stats if user mentions a brand
        $brandMatch = $this->detectBrandInMessage($userMessage, $categoryId);
        if ($brandMatch) {
            $brandProducts = Supplement::where('category_id', $categoryId)
                ->where('brand', $brandMatch)
                ->orderByDesc('overall_recommendation_score')
                ->limit(5)
                ->get();

            if ($brandProducts->isNotEmpty()) {
                $avgScore = round($brandProducts->avg('overall_recommendation_score'), 1);
                $trustScore = $brandProducts->first()->brand_trust_score;
                $sections[] = "\n## Μάρκα: {$brandMatch} (Trust: {$trustScore}/10, Avg: {$avgScore}/10)";
                foreach ($brandProducts as $s) {
                    $sections[] = "- {$s->title} | Score: {$s->overall_recommendation_score} | Dose: {$s->clinical_dose_score}";
                }
            }
        }

        // Category stats
        $formStats = Supplement::where('category_id', $categoryId)
            ->whereNotNull('dosage_form')
            ->selectRaw('dosage_form, COUNT(*) as count, ROUND(AVG(overall_recommendation_score), 1) as avg_score')
            ->groupBy('dosage_form')
            ->orderByDesc('avg_score')
            ->get();

        if ($formStats->isNotEmpty()) {
            $sections[] = "\n## Μορφές δόσης στην κατηγορία:";
            foreach ($formStats as $f) {
                $sections[] = "- {$f->dosage_form}: {$f->count} προϊόντα (avg score: {$f->avg_score})";
            }
        }

        return implode("\n", $sections) . "\n";
    }

    /**
     * Extract search-worthy terms from user message.
     */
    private function extractSearchTerms(string $message): array
    {
        $message = strtolower($message);

        // Remove common Greek/English stop words
        $stopWords = ['θέλω', 'για', 'ένα', 'μου', 'τι', 'ποιο', 'είναι', 'πιο', 'να', 'πάρω', 'the', 'best', 'what', 'which', 'good', 'want', 'need', 'have', 'can', 'you', 'me', 'my', 'is', 'are', 'do', 'a', 'an', 'to', 'for', 'of', 'and', 'in', 'with'];

        $words = preg_split('/[\s,;]+/', $message);
        $terms = [];

        foreach ($words as $word) {
            $word = trim($word, '?!.');
            if (mb_strlen($word) >= 3 && !in_array($word, $stopWords)) {
                $terms[] = $word;
            }
        }

        // Keep max 3 most relevant terms
        return array_slice($terms, 0, 3);
    }

    /**
     * Detect if user mentions a specific brand in their message.
     */
    private function detectBrandInMessage(string $message, int $categoryId): ?string
    {
        $message = strtolower($message);

        // Get top brands in this category
        $brands = Supplement::where('category_id', $categoryId)
            ->select('brand')
            ->distinct()
            ->pluck('brand');

        foreach ($brands as $brand) {
            if (str_contains($message, strtolower($brand))) {
                return $brand;
            }
        }

        return null;
    }

    /**
     * Detect if user wants to compare products and build comparison context.
     */
    private function buildComparisonContext(string $userMessage, ?int $categoryId): string
    {
        $message = strtolower($userMessage);

        // Detect comparison intent
        $compareKeywords = ['compare', 'σύγκριση', 'σύγκρινε', 'διαφορά', 'διαφορές', 'vs', 'ή', 'καλύτερο', ' or ', 'difference', 'versus'];
        $wantsCompare = false;
        foreach ($compareKeywords as $kw) {
            if (str_contains($message, $kw)) {
                $wantsCompare = true;
                break;
            }
        }

        if (!$wantsCompare) return '';

        // Try to find 2+ brand/product names in the message
        $query = Supplement::query();
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Get distinct brands in category
        $brands = $query->select('brand')->distinct()->pluck('brand');
        $matchedBrands = [];
        foreach ($brands as $brand) {
            if (str_contains($message, strtolower($brand))) {
                $matchedBrands[] = $brand;
            }
        }

        if (count($matchedBrands) < 2) return '';

        // Pull top product from each matched brand
        $sections = ["\n# ΣΥΓΚΡΙΣΗ ΠΡΟΪΟΝΤΩΝ\n"];
        $sections[] = "Ο χρήστης θέλει σύγκριση. Δώσε ΑΝΑΛΥΤΙΚΗ σύγκριση με βάση τα παρακάτω δεδομένα:\n";

        foreach ($matchedBrands as $brand) {
            $products = Supplement::where('brand', $brand)
                ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                ->orderByDesc('overall_recommendation_score')
                ->limit(2)
                ->get();

            foreach ($products as $s) {
                $ingredients = '';
                if (!empty($s->active_ingredients) && is_array($s->active_ingredients)) {
                    $ingList = array_map(fn($ing) => ($ing['name'] ?? '') . ' ' . ($ing['amount'] ?? '') . ($ing['unit'] ?? ''), array_slice($s->active_ingredients, 0, 4));
                    $ingredients = implode(', ', $ingList);
                }
                $flags = !empty($s->red_flags_detected) ? 'RED FLAGS: ' . implode(', ', $s->red_flags_detected) : 'Χωρίς red flags';
                $certs = !empty($s->certification_flags) && is_array($s->certification_flags) ? implode(', ', array_slice($s->certification_flags, 0, 3)) : 'Χωρίς πιστοποιήσεις';

                $sections[] = "## {$s->brand} - {$s->title}";
                $sections[] = "Score: {$s->overall_recommendation_score}/10 | Rank: #{$s->category_rank} | Dose: {$s->clinical_dose_score} | Bio: {$s->bioavailability_score} | Quality: {$s->quality_score}";
                $sections[] = "Brand Trust: {$s->brand_trust_score}/10 | Form: {$s->dosage_form} | Servings: {$s->servings_per_container}";
                $sections[] = "Ingredients: {$ingredients}";
                $sections[] = "Certifications: {$certs}";
                $sections[] = "{$flags}\n";
            }
        }

        $sections[] = "ΟΔΗΓΙΕΣ: Σύγκρινε τα προϊόντα σε καθαρό κείμενο. Πες ξεκάθαρα ποιο είναι καλύτερο και ΓΙΑΤΙ (μορφή, δόση, απορρόφηση, red flags). Μην χρησιμοποιείς markdown.";

        return implode("\n", $sections) . "\n";
    }

    /**
     * Build clinical knowledge section from clinical_doses.php config
     */
    private function buildClinicalKnowledge(?string $categorySlug): string
    {
        if (!$categorySlug) return '';

        $config = config("clinical_doses.{$categorySlug}");
        if (!$config) return '';

        $sections = ["# ΚΛΙΝΙΚΗ ΓΝΩΣΗ ΓΙΑ ΑΥΤΗ ΤΗΝ ΚΑΤΗΓΟΡΙΑ\n"];

        // Therapeutic range
        if (!empty($config['therapeutic_range'])) {
            $r = $config['therapeutic_range'];
            $sections[] = "## Θεραπευτικό εύρος δόσης";
            $sections[] = "- Ελάχιστη: {$r['min']} {$r['unit']}";
            $sections[] = "- Βέλτιστη: {$r['optimal']} {$r['unit']}";
            $sections[] = "- Μέγιστη ασφαλής: {$r['max']} {$r['unit']}";
        }

        // Preferred forms
        if (!empty($config['preferred_forms'])) {
            $sections[] = "\n## Καλύτερες μορφές (από καλύτερη σε χειρότερη)";
            arsort($config['preferred_forms']);
            foreach ($config['preferred_forms'] as $form => $rating) {
                $pct = round($rating * 100);
                $sections[] = "- **{$form}**: {$pct}% βαθμολογία απορρόφησης";
            }
        }

        // Penalized forms
        if (!empty($config['penalized_forms'])) {
            $sections[] = "\n## Κακές μορφές (ΑΠΟΦΥΓΗ)";
            foreach ($config['penalized_forms'] as $form => $penalty) {
                $sections[] = "- **{$form}**: ποινή {$penalty} — ΧΑΜΗΛΗ απορρόφηση";
            }
        }

        // Red flags
        if (!empty($config['red_flags'])) {
            $sections[] = "\n## Red flags (προειδοποιήσεις ποιότητας)";
            foreach ($config['red_flags'] as $flag) {
                $sections[] = "- {$flag}";
            }
        }

        // Cofactors
        if (!empty($config['cofactors'])) {
            $sections[] = "\n## Συνεργιστικά συστατικά (cofactors)";
            $sections[] = "- " . implode(', ', $config['cofactors']);
        }

        // Clinical notes
        if (!empty($config['notes'])) {
            $sections[] = "\n## Κλινικές σημειώσεις";
            $sections[] = $config['notes'];
        }

        return implode("\n", $sections) . "\n";
    }

    /**
     * Build interaction warnings section for the AI prompt
     */
    private function buildInteractionWarnings(?string $categorySlug): string
    {
        if (!$categorySlug) return '';

        $interactions = config('interactions', []);
        if (empty($interactions)) return '';

        $relevant = [];
        $categoryKeywords = [
            'omega-3-fish-oil' => ['omega-3', 'omega'],
            'magnesium' => ['magnesium'],
            'vitamin-d' => ['vitamin-d'],
            'calcium' => ['calcium'],
            'iron' => ['iron'],
            'zinc' => ['zinc'],
            'coq10' => ['coq10'],
            'collagen' => ['collagen'],
            'vitamin-c' => ['vitamin-c'],
            'sleep-support' => ['melatonin'],
            'herbal-adaptogens' => ['curcumin', 'st-johns-wort'],
            'vitamins-other' => ['vitamin-e'],
        ];

        $keywords = $categoryKeywords[$categorySlug] ?? [$categorySlug];

        foreach ($interactions as $interaction) {
            foreach ($keywords as $kw) {
                if (str_contains($interaction['supplement_a'], $kw) || str_contains($interaction['supplement_b'], $kw)) {
                    $relevant[] = $interaction;
                    break;
                }
            }
        }

        if (empty($relevant)) return '';

        $sections = ["\n# ΑΛΛΗΛΕΠΙΔΡΑΣΕΙΣ & ΠΡΟΕΙΔΟΠΟΙΗΣΕΙΣ\n"];
        $sections[] = "ΣΗΜΑΝΤΙΚΟ: Αν ο χρήστης αναφέρει ότι παίρνει κάποιο από τα παρακάτω, ΠΡΕΠΕΙ να τον ενημερώσεις.\n";

        foreach ($relevant as $i) {
            $type = match ($i['type']) {
                'avoid' => 'ΑΠΟΦΥΓΗ',
                'separate' => 'ΞΕΧΩΡΙΣΤΑ',
                'monitor' => 'ΠΑΡΑΚΟΛΟΥΘΗΣΗ',
                'synergy' => 'ΣΥΝΕΡΓΙΑ',
                default => $i['type'],
            };
            $sections[] = "- [{$type}] {$i['supplement_a']} + {$i['supplement_b']}: {$i['warning_el']}";
        }

        return implode("\n", $sections) . "\n";
    }

    /**
     * Build the recommendation system prompt
     */
    private function buildRecommendationPrompt($category, $conditions, $preferences, $supplementsJson): string
    {
        $categoryName = $category->name ?? 'συμπληρώματα';
        $conditionsList = !empty($conditions) ? implode(', ', $conditions) : 'γενική ευεξία';
        $budget = $preferences['budget'] ?? null;
        $allergies = $preferences['allergies'] ?? [];
        $dietary = $preferences['dietary'] ?? [];

        $constraintsText = '';
        if ($budget) {
            $constraintsText .= "- Budget: έως €{$budget}\n";
        }
        if (!empty($allergies)) {
            $constraintsText .= "- Αλλεργίες: " . implode(', ', $allergies) . "\n";
        }
        if (!empty($dietary)) {
            $constraintsText .= "- Διατροφικοί περιορισμοί: " . implode(', ', $dietary) . "\n";
        }

        // Build evidence-based knowledge section
        $evidenceSection = $this->buildEvidenceSection($categoryName, $conditions);
        $clinicalKnowledge = $this->buildClinicalKnowledge($category->slug ?? null);
        $interactionWarnings = $this->buildInteractionWarnings($category->slug ?? null);

        return <<<PROMPT
# ΡΟΛΟΣ

Είσαι ειδικός διατροφολόγος που αξιολογεί συμπληρώματα βάσει κλινικών κριτηρίων. Απαντάς στα Ελληνικά.

# ΑΠΟΣΤΟΛΗ

Ανάλυσε τα διαθέσιμα συμπληρώματα και επέλεξε τα TOP 5 για τον χρήστη.
ΧΡΗΣΙΜΟΠΟΙΗΣΕ την κλινική γνώση παρακάτω για να ΕΞΗΓΗΣΕΙΣ γιατί κάποια μορφή είναι καλύτερη, γιατί η δόση είναι ανεπαρκής, ή γιατί υπάρχουν red flags. Ο χρήστης πρέπει να ΚΑΤΑΛΑΒΕΙ, όχι απλά να δει βαθμολογίες.

# ΣΤΟΙΧΕΙΑ ΧΡΗΣΤΗ

- **Κατηγορία:** {$categoryName}
- **Καταστάσεις/Στόχοι:** {$conditionsList}
{$constraintsText}
{$clinicalKnowledge}
{$evidenceSection}
# ΣΥΣΤΗΜΑ ΒΑΘΜΟΛΟΓΗΣΗΣ (0-10) — Καθαρά κλινικό, χωρίς τιμή/κριτικές

Τα συμπληρώματα έχουν προ-υπολογισμένες βαθμολογίες:

1. **clinical_dose_score** - Επάρκεια δόσης vs θεραπευτικό εύρος
   - 10 = βέλτιστη δόση, 5 = ελάχιστη, 1 = σοβαρά ανεπαρκής

2. **efficacy_score (40%)** - Κλινική αποτελεσματικότητα
   - Δόση (50%) + Μορφή συστατικού (30%) + Συνεργιστικά (20%)

3. **bioavailability_score (25%)** - Βιοδιαθεσιμότητα
   - Μορφή συστατικού (70%) + Μέθοδος παράδοσης (30%)

4. **quality_score (20%)** - Ποιότητα & Ασφάλεια
   - Πιστοποιήσεις (50%) + Πληρότητα δεδομένων (30%) + Απουσία red flags (20%)

5. **formulation_score (15%)** - Ποιότητα σύνθεσης

6. **brand_trust_score** - Αξιοπιστία μάρκας (μέσος όρος σε όλα τα προϊόντα)

7. **category_rank** - Κατάταξη εντός κατηγορίας (#1 = καλύτερο)

8. **red_flags** - Κλινικές προειδοποιήσεις (π.χ. "Severely underdosed", "Poor form")

**overall_recommendation_score** = Σταθμισμένος μέσος όρος χωρίς τιμή/κριτικές

# ΚΑΝΟΝΕΣ ΕΠΙΛΟΓΗΣ

1. **Προτεραιότητα στη βιοδιαθεσιμότητα** - Ένα φθηνό συμπλήρωμα με χαμηλή απορρόφηση είναι χειρότερο από ακριβότερο με υψηλή
2. **Εξήγησε το ΓΙΑΤΙ** - Ο χρήστης πρέπει να καταλάβει γιατί το #1 είναι καλύτερο από το #2
3. **Αναφορά στις καταστάσεις** - Σύνδεσε το συμπλήρωμα με τις ανάγκες του χρήστη
4. **Ρεαλιστικές δοσολογίες** - Βάσει των ενεργών συστατικών

# ΜΟΡΦΗ ΕΞΗΓΗΣΗΣ (why_best)

Γράψε 2-3 προτάσεις σε ΚΑΘΑΡΟ ΚΕΙΜΕΝΟ (χωρίς markdown, χωρίς **, χωρίς bullets).
Εξήγησε: ποια μορφή περιέχει, γιατί βοηθά τον χρήστη, τι το ξεχωρίζει.

Παράδειγμα:
"Περιέχει μαγνήσιο bisglycinate με πολύ υψηλή απορρόφηση, τη μορφή με την καλύτερη βιοδιαθεσιμότητα. Ιδανικό για διαβήτη καθώς μελέτες δείχνουν βελτίωση ευαισθησίας ινσουλίνης. Third-party tested για εγγυημένη καθαρότητα."

{$interactionWarnings}
# ΠΡΟΕΙΔΟΠΟΙΗΣΕΙΣ ΑΣΦΑΛΕΙΑΣ

- Αν κάποιο συμπλήρωμα έχει αντενδείξεις για τις καταστάσεις του χρήστη, ΜΗΝ το προτείνεις
- Πάντα να αναφέρεις: "Συμβουλευτείτε τον γιατρό σας πριν ξεκινήσετε"
- Αν ο χρήστης αναφέρει φάρμακα ή άλλα συμπληρώματα, ΕΛΕΓΞΕ για αλληλεπιδράσεις στη λίστα παραπάνω

# ΔΙΑΘΕΣΙΜΑ ΣΥΜΠΛΗΡΩΜΑΤΑ

{$supplementsJson}

# FORMAT ΑΠΑΝΤΗΣΗΣ

Απάντησε ΜΟΝΟ με valid JSON:
{
  "recommendations": [
    {
      "id": 123,
      "rank": 1,
      "why_best": "Ελληνική εξήγηση 2-3 προτάσεις",
      "dosage": "Συνιστώμενη δοσολογία στα Ελληνικά"
    }
  ],
  "comparison": "Σύντομη σύγκριση top 3 (3-4 προτάσεις) εξηγώντας τις διαφορές",
  "disclaimer": "Τα συμπληρώματα δεν αντικαθιστούν την ιατρική συμβουλή. Συμβουλευτείτε τον γιατρό σας."
}

PROMPT;
    }

    /**
     * Calculate evidence-based boost for a supplement based on conditions
     */
    private function calculateEvidenceBoost(Supplement $supplement, array $conditions, string $categoryName): float
    {
        $boost = 0;
        $mappings = SupplementKnowledgeBase::getConditionMappings();

        foreach ($conditions as $condition) {
            foreach ($mappings as $condName => $data) {
                if (stripos($condName, $condition) !== false || stripos($condition, $condName) !== false) {
                    // Check if this category is recommended for the condition
                    foreach ($data['recommended'] as $supp => $info) {
                        if (stripos($supp, $categoryName) !== false || stripos($categoryName, $supp) !== false) {
                            // Evidence level boost
                            $evidenceBoost = match($info['evidence'] ?? 'moderate') {
                                'strong' => 1.0,
                                'moderate' => 0.5,
                                'limited' => 0.2,
                                default => 0
                            };
                            $boost += $evidenceBoost;

                            // Check if supplement contains preferred forms
                            if (!empty($info['forms']) && $supplement->active_ingredients) {
                                $ingredients = is_array($supplement->active_ingredients)
                                    ? $supplement->active_ingredients
                                    : json_decode($supplement->active_ingredients, true) ?? [];

                                foreach ($ingredients as $ingredient) {
                                    $ingredientName = strtolower($ingredient['name'] ?? '');
                                    foreach ($info['forms'] as $form => $score) {
                                        if (stripos($ingredientName, $form) !== false) {
                                            // High bioavailability form found - add boost
                                            $boost += ($score / 10) * 0.5;
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Check for contraindications
                    if (!empty($data['contraindicated'])) {
                        foreach ($data['contraindicated'] as $supp => $reason) {
                            if (stripos($supp, $categoryName) !== false) {
                                $boost -= 2; // Significant penalty for contraindicated
                            }
                        }
                    }
                    break;
                }
            }
        }

        return $boost;
    }

    /**
     * Calculate bioavailability score based on ingredient forms
     */
    private function calculateBioavailabilityFromKnowledge(Supplement $supplement, string $categoryName): float
    {
        $bioData = SupplementKnowledgeBase::getBioavailabilityData();
        $ingredients = $supplement->active_ingredients;

        if (!$ingredients) {
            return $supplement->bioavailability_score ?? 5.0;
        }

        $ingredients = is_array($ingredients) ? $ingredients : json_decode($ingredients, true) ?? [];
        $maxScore = 0;

        foreach ($bioData as $cat => $forms) {
            if (stripos($cat, $categoryName) !== false || stripos($categoryName, $cat) !== false) {
                foreach ($ingredients as $ingredient) {
                    $ingredientName = strtolower($ingredient['name'] ?? '');
                    foreach ($forms as $form => $info) {
                        if (stripos($ingredientName, strtolower($form)) !== false) {
                            $maxScore = max($maxScore, $info['score']);
                        }
                    }
                }
                break;
            }
        }

        return $maxScore > 0 ? $maxScore : ($supplement->bioavailability_score ?? 5.0);
    }

    private function generateRecommendations($conversation, ?UserProfile $userProfile = null): array
    {
        $category = SupplementCategory::find($conversation->selected_category_id);
        $conditions = $conversation->extracted_conditions ?? [];
        $preferences = $conversation->extracted_preferences ?? [];
        $categoryName = $category->name ?? '';

        // Merge user profile knowledge with current session preferences
        if ($userProfile) {
            // Add known conditions from profile
            $profileConditions = $userProfile->known_conditions ?? [];
            $conditions = array_unique(array_merge($conditions, $profileConditions));

            // Add known allergies to preferences
            $profileAllergies = $userProfile->known_allergies ?? [];
            $preferences['allergies'] = array_unique(array_merge($preferences['allergies'] ?? [], $profileAllergies));

            // Use profile budget if not set in current session
            if (empty($preferences['budget']) && $userProfile->budget_preference) {
                $preferences['budget'] = $userProfile->budget_preference;
            }

            // Add dietary preferences from profile
            $profileDietary = $userProfile->dietary_preferences ?? [];
            $preferences['dietary'] = array_unique(array_merge($preferences['dietary'] ?? [], $profileDietary));
        }

        // Get condition IDs for mapping-based scoring
        $conditionIds = MedicalCondition::whereIn('name', $conditions)->pluck('id');

        // Build query with proper scoring
        $query = Supplement::where('category_id', $conversation->selected_category_id)
            ->whereNotNull('overall_recommendation_score');

        // Apply budget filter if set
        if (!empty($preferences['budget'])) {
            $query->where('current_price', '<=', $preferences['budget']);
        }

        // Get base supplements
        $supplements = $query->orderBy('overall_recommendation_score', 'desc')->limit(100)->get();

        // Apply evidence-based scoring
        $supplements = $supplements->map(function ($s) use ($conditions, $categoryName) {
            // Calculate evidence boost
            $evidenceBoost = $this->calculateEvidenceBoost($s, $conditions, $categoryName);

            // Calculate bioavailability from knowledge base
            $knowledgeBioScore = $this->calculateBioavailabilityFromKnowledge($s, $categoryName);

            // Create adjusted score
            $baseScore = $s->overall_recommendation_score ?? 5.0;
            $s->adjusted_score = $baseScore + $evidenceBoost;
            $s->knowledge_bio_score = $knowledgeBioScore;
            $s->evidence_boost = $evidenceBoost;

            return $s;
        });

        // Sort by adjusted score
        $supplements = $supplements->sortByDesc('adjusted_score')->take(50)->values();

        // Prepare supplement data for AI (top 25 for context)
        $supplementsJson = $supplements->take(25)->map(fn($s) => [
            'id' => $s->id,
            'brand' => $s->brand,
            'title' => $s->title,
            'overall_score' => $s->overall_recommendation_score,
            'clinical_dose_score' => $s->clinical_dose_score,
            'efficacy_score' => $s->efficacy_score,
            'quality_score' => $s->quality_score,
            'bioavailability_score' => $s->bioavailability_score,
            'formulation_score' => $s->formulation_score,
            'category_rank' => $s->category_rank,
            'brand_trust_score' => $s->brand_trust_score,
            'red_flags' => $s->red_flags_detected,
            'dosage_form' => $s->dosage_form,
            'active_ingredients' => $s->active_ingredients,
            'certification_flags' => $s->certification_flags,
            'serving_size' => $s->serving_size_value . ' ' . $s->serving_size_unit,
            'servings_per_container' => $s->servings_per_container,
        ])->toJson(JSON_UNESCAPED_UNICODE);

        $prompt = $this->buildRecommendationPrompt($category, $conditions, $preferences, $supplementsJson);

        // Call AI service
        $result = $this->ai->chat($prompt, [], 'recommendation');

        if ($result['success'] && !empty($result['content'])) {
            $text = $result['content'];

            // Clean up response
            $text = preg_replace('/```json\s*/i', '', $text);
            $text = preg_replace('/```\s*/', '', $text);
            $text = trim($text);

            $data = json_decode($text, true);

            if ($data && isset($data['recommendations'])) {
                // Enrich recommendations with full supplement data
                $enrichedRecs = [];
                foreach ($data['recommendations'] as $rec) {
                    $supplement = $supplements->firstWhere('id', $rec['id']);
                    if ($supplement) {
                        $enrichedRecs[] = [
                            'id' => $supplement->id,
                            'rank' => $rec['rank'],
                            'brand' => $supplement->brand,
                            'title' => $supplement->title,
                            'score' => $supplement->overall_recommendation_score,
                            'clinical_dose_score' => $supplement->clinical_dose_score,
                            'efficacy_score' => $supplement->efficacy_score,
                            'quality_score' => $supplement->quality_score,
                            'bioavailability_score' => $supplement->bioavailability_score,
                            'formulation_score' => $supplement->formulation_score,
                            'brand_trust_score' => $supplement->brand_trust_score,
                            'category_rank' => $supplement->category_rank,
                            'red_flags' => $supplement->red_flags_detected ?? [],
                            'dosage_form' => $supplement->dosage_form,
                            'active_ingredients' => $supplement->active_ingredients,
                            'certification_flags' => $supplement->certification_flags,
                            'servings_per_container' => $supplement->servings_per_container,
                            'image_url' => $supplement->image_url,
                            'product_url' => $supplement->product_url,
                            'why_best' => $rec['why_best'] ?? '',
                            'dosage' => $rec['dosage'] ?? '',
                        ];
                    }
                }

                return [
                    'recommendations' => $enrichedRecs,
                    'comparison' => $data['comparison'] ?? '',
                    'disclaimer' => $data['disclaimer'] ?? 'Τα συμπληρώματα δεν αντικαθιστούν την ιατρική συμβουλή. Συμβουλευτείτε τον γιατρό σας.'
                ];
            }
        }

        Log::warning('AI recommendation failed, using fallback', ['error' => $result['error'] ?? 'Invalid JSON']);

        // Fallback: return top 5 by calculated overall_recommendation_score
        return [
            'recommendations' => $supplements->sortByDesc('overall_recommendation_score')->take(5)->values()->map(fn($s, $idx) => [
                'id' => $s->id,
                'rank' => $idx + 1,
                'brand' => $s->brand,
                'title' => $s->title,
                'price' => $s->current_price,
                'rating' => $s->rating,
                'review_count' => $s->review_count,
                'score' => $s->overall_recommendation_score ?? round((($s->rating ?? 4.0) / 5) * 10, 2),
                'efficacy_score' => $s->efficacy_score,
                'quality_score' => $s->quality_score,
                'bioavailability_score' => $s->bioavailability_score,
                'formulation_score' => $s->formulation_score,
                'image_url' => $s->image_url,
                'product_url' => $s->product_url,
                'why_best' => $this->getDefaultWhyBest($s, $conditions),
                'dosage' => $this->getDefaultDosage($s),
            ])->values()->toArray(),
            'comparison' => 'Οι κορυφαίες επιλογές βάσει του κλινικού αλγορίθμου βαθμολόγησης που λαμβάνει υπόψη την αποτελεσματικότητα (35%), την ποιότητα (30%), τη βιοδιαθεσιμότητα (25%) και τη σύνθεση (10%).',
            'disclaimer' => 'Τα συμπληρώματα δεν αντικαθιστούν την ιατρική συμβουλή. Συμβουλευτείτε τον γιατρό σας πριν ξεκινήσετε οποιοδήποτε συμπλήρωμα.'
        ];
    }

    /**
     * Generate default explanation for a supplement
     */
    private function getDefaultWhyBest(Supplement $s, array $conditions): string
    {
        $reasons = [];

        // Score-based reasons
        if ($s->bioavailability_score >= 8) {
            $reasons[] = 'Υψηλή βιοδιαθεσιμότητα για μέγιστη απορρόφηση';
        }
        if ($s->efficacy_score >= 8) {
            $reasons[] = 'Εξαιρετική αποτελεσματικότητα βάσει κλινικών δεδομένων';
        }
        if ($s->quality_score >= 7) {
            $reasons[] = 'Πιστοποιημένη ποιότητα με αυστηρούς ελέγχους';
        }
        if ($s->formulation_score >= 8) {
            $reasons[] = 'Άριστη σύνθεση με συνεργιστικά συστατικά';
        }
        if ($s->rating >= 4.5 && $s->review_count >= 100) {
            $reasons[] = "Εξαιρετικές κριτικές από {$s->review_count}+ χρήστες";
        }

        // Certification-based reasons
        $certs = $s->certification_flags ?? [];
        if (in_array('third-party tested', $certs) || in_array('third_party_tested', $certs)) {
            $reasons[] = 'Ανεξάρτητα ελεγμένο για καθαρότητα';
        }
        if (in_array('gmp', $certs) || in_array('GMP', $certs)) {
            $reasons[] = 'Παραγωγή σε εγκαταστάσεις GMP';
        }

        if (empty($reasons)) {
            return 'Προτεινόμενο προϊόν βάσει συνολικής κλινικής βαθμολογίας.';
        }

        return implode('. ', array_slice($reasons, 0, 3)) . '.';
    }

    /**
     * Generate default dosage recommendation
     */
    private function getDefaultDosage(Supplement $s): string
    {
        $form = $s->dosage_form ?? 'δόση';
        $serving = $s->serving_size_value ?? 1;
        $unit = $s->serving_size_unit ?? '';

        $formTranslations = [
            'capsule' => 'κάψουλα',
            'softgel' => 'κάψουλα',
            'tablet' => 'δισκίο',
            'powder' => 'σκόνη',
            'liquid' => 'υγρό',
            'gummy' => 'ζελεδάκι',
        ];

        $greekForm = $formTranslations[$form] ?? $form;

        if ($serving && $unit) {
            return "{$serving} {$greekForm} ({$unit}) ημερησίως με φαγητό, ή σύμφωνα με τις οδηγίες του γιατρού σας.";
        }

        return "Ακολουθήστε τις οδηγίες στη συσκευασία ή συμβουλευτείτε τον γιατρό σας για την κατάλληλη δοσολογία.";
    }
}
