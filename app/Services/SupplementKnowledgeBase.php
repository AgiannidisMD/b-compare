<?php

namespace App\Services;

/**
 * Evidence-based knowledge base for supplement recommendations
 * Contains condition mappings, bioavailability data, contraindications, and Greek terminology
 */
class SupplementKnowledgeBase
{
    /**
     * Condition-specific supplement recommendations with evidence levels
     * Evidence levels: strong, moderate, limited, anecdotal
     */
    public static function getConditionMappings(): array
    {
        return [
            'Diabetes' => [
                'recommended' => [
                    'Magnesium' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Βελτιώνει την ευαισθησία στην ινσουλίνη και τη ρύθμιση του σακχάρου',
                        'forms' => ['bisglycinate' => 9, 'citrate' => 7, 'oxide' => 3],
                        'dosage' => '300-400mg ημερησίως',
                        'studies' => 'Meta-analysis 2019: 18% βελτίωση ευαισθησίας ινσουλίνης',
                    ],
                    'Vitamin D' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Βελτιώνει τη λειτουργία των β-κυττάρων του παγκρέατος',
                        'forms' => ['D3 cholecalciferol' => 9, 'D2 ergocalciferol' => 5],
                        'dosage' => '2000-4000 IU ημερησίως',
                        'studies' => 'Cochrane Review: Σημαντική μείωση HbA1c',
                    ],
                    'Omega-3 & Fish Oil' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Μειώνει τη φλεγμονή και τα τριγλυκερίδια',
                        'forms' => ['triglyceride' => 9, 'ethyl ester' => 6],
                        'dosage' => '1-2g EPA+DHA ημερησίως',
                    ],
                    'Chromium' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Ενισχύει τη δράση της ινσουλίνης',
                        'forms' => ['picolinate' => 8, 'polynicotinate' => 7],
                        'dosage' => '200-1000mcg ημερησίως',
                    ],
                ],
                'contraindicated' => [],
                'caution' => ['Iron' => 'Μπορεί να επηρεάσει τον έλεγχο σακχάρου σε υψηλές δόσεις'],
            ],

            'Cardiovascular Disease' => [
                'recommended' => [
                    'Omega-3 & Fish Oil' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Μειώνει τριγλυκερίδια, φλεγμονή, αρρυθμίες',
                        'forms' => ['triglyceride' => 9, 'ethyl ester' => 6, 'phospholipid' => 8],
                        'dosage' => '2-4g EPA+DHA ημερησίως για υψηλά τριγλυκερίδια',
                        'studies' => 'REDUCE-IT: 25% μείωση καρδιαγγειακών συμβαμάτων',
                    ],
                    'CoQ10' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Παραγωγή ενέργειας στην καρδιά, αντιοξειδωτική δράση',
                        'forms' => ['ubiquinol' => 9, 'ubiquinone' => 6],
                        'dosage' => '100-300mg ημερησίως',
                        'studies' => 'Q-SYMBIO: 43% μείωση θνησιμότητας σε καρδιακή ανεπάρκεια',
                    ],
                    'Magnesium' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Χαλαρώνει τα αγγεία, ρυθμίζει την πίεση',
                        'dosage' => '300-400mg ημερησίως',
                    ],
                ],
                'contraindicated' => [],
                'caution' => ['Vitamin E' => 'Υψηλές δόσεις μπορεί να αυξήσουν τον κίνδυνο αιμορραγίας'],
            ],

            'Hypertension' => [
                'recommended' => [
                    'Magnesium' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Χαλαρώνει τα αγγεία, φυσικός αποκλειστής ασβεστίου',
                        'forms' => ['taurate' => 9, 'bisglycinate' => 8, 'citrate' => 7],
                        'dosage' => '300-500mg ημερησίως',
                        'studies' => 'Meta-analysis: Μείωση 2-3 mmHg συστολικής πίεσης',
                    ],
                    'CoQ10' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Βελτιώνει την ενδοθηλιακή λειτουργία',
                        'dosage' => '100-200mg ημερησίως',
                    ],
                    'Omega-3 & Fish Oil' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Μειώνει τη φλεγμονή των αγγείων',
                        'dosage' => '2-3g ημερησίως',
                    ],
                ],
                'contraindicated' => [],
                'caution' => ['Licorice' => 'Αυξάνει την πίεση'],
            ],

            'Arthritis' => [
                'recommended' => [
                    'Omega-3 & Fish Oil' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Ισχυρή αντιφλεγμονώδης δράση, μειώνει τη δυσκαμψία',
                        'dosage' => '2-3g EPA+DHA ημερησίως',
                        'studies' => 'Cochrane: Σημαντική μείωση πόνου και φλεγμονής',
                    ],
                    'Collagen' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Υποστηρίζει την αναγέννηση του χόνδρου',
                        'forms' => ['type II undenatured' => 9, 'hydrolyzed' => 7],
                        'dosage' => '10g υδρολυμένο ή 40mg UC-II ημερησίως',
                    ],
                    'Joint Support' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Γλυκοζαμίνη + χονδροϊτίνη: δομικά στοιχεία χόνδρου',
                        'dosage' => '1500mg γλυκοζαμίνη + 1200mg χονδροϊτίνη',
                    ],
                ],
                'contraindicated' => [],
                'caution' => [],
            ],

            'Osteoporosis' => [
                'recommended' => [
                    'Calcium' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Δομικό συστατικό των οστών',
                        'forms' => ['citrate' => 9, 'carbonate' => 7, 'hydroxyapatite' => 8],
                        'dosage' => '500-600mg δύο φορές ημερησίως',
                    ],
                    'Vitamin D' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Απαραίτητη για την απορρόφηση ασβεστίου',
                        'dosage' => '2000-4000 IU ημερησίως',
                    ],
                    'Magnesium' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Συμμετέχει στον μεταβολισμό της βιταμίνης D και του ασβεστίου',
                        'dosage' => '300-400mg ημερησίως',
                    ],
                ],
                'contraindicated' => [],
                'caution' => [],
            ],

            'Digestive Issues' => [
                'recommended' => [
                    'Probiotics' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Αποκαθιστά την ισορροπία της εντερικής χλωρίδας',
                        'forms' => ['multi-strain' => 9, 'single-strain' => 6],
                        'dosage' => '10-50 δισεκατομμύρια CFU ημερησίως',
                        'studies' => 'Meta-analysis: Αποτελεσματικά για IBS, διάρροια, δυσκοιλιότητα',
                    ],
                    'Digestive Enzymes' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Βοηθά στην πέψη πρωτεϊνών, λιπών, υδατανθράκων',
                        'dosage' => 'Με κάθε γεύμα',
                    ],
                    'Fiber Supplements' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Ρυθμίζει την κινητικότητα, τρέφει τα προβιοτικά',
                        'forms' => ['psyllium' => 9, 'inulin' => 8, 'acacia' => 7],
                        'dosage' => '5-10g ημερησίως με αρκετό νερό',
                    ],
                ],
                'contraindicated' => [],
                'caution' => [],
            ],

            'Immune Support' => [
                'recommended' => [
                    'Vitamin C' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Υποστηρίζει τη λειτουργία των λευκών αιμοσφαιρίων',
                        'forms' => ['ascorbic acid' => 7, 'liposomal' => 9, 'buffered' => 8],
                        'dosage' => '500-1000mg ημερησίως, έως 2000mg σε ασθένεια',
                    ],
                    'Vitamin D' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Ρυθμίζει την ανοσολογική απόκριση',
                        'dosage' => '2000-4000 IU ημερησίως',
                    ],
                    'Zinc' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Απαραίτητο για τη λειτουργία των Τ-κυττάρων',
                        'forms' => ['picolinate' => 9, 'citrate' => 8, 'gluconate' => 7, 'oxide' => 4],
                        'dosage' => '15-30mg ημερησίως',
                    ],
                ],
                'contraindicated' => [],
                'caution' => ['Zinc' => 'Μην υπερβαίνετε τα 40mg/ημέρα μακροπρόθεσμα'],
            ],

            'Anxiety' => [
                'recommended' => [
                    'Magnesium' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Ρυθμίζει τον άξονα HPA, χαλαρώνει το νευρικό σύστημα',
                        'forms' => ['glycinate' => 9, 'taurate' => 8, 'threonate' => 9],
                        'dosage' => '300-400mg ημερησίως',
                    ],
                    'Herbal/Adaptogens' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Ashwagandha μειώνει την κορτιζόλη, L-theanine προάγει χαλάρωση',
                        'dosage' => 'Ashwagandha: 300-600mg, L-theanine: 200-400mg',
                    ],
                    'B-Vitamins' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Απαραίτητα για την παραγωγή νευροδιαβιβαστών',
                        'dosage' => 'B-Complex με ενεργές μορφές',
                    ],
                ],
                'contraindicated' => [],
                'caution' => [],
            ],

            'Sleep Disorders' => [
                'recommended' => [
                    'Magnesium' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Ενεργοποιεί το παρασυμπαθητικό σύστημα',
                        'forms' => ['glycinate' => 9, 'taurate' => 8],
                        'dosage' => '300-400mg πριν τον ύπνο',
                    ],
                    'Sleep Support' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Μελατονίνη ρυθμίζει τον κιρκαδιανό ρυθμό',
                        'dosage' => 'Μελατονίνη: 0.5-5mg πριν τον ύπνο',
                    ],
                    'Herbal/Adaptogens' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Βαλεριάνα, Passionflower: ήπια ηρεμιστική δράση',
                        'dosage' => 'Βαλεριάνα: 300-600mg πριν τον ύπνο',
                    ],
                ],
                'contraindicated' => [],
                'caution' => [],
            ],

            'Athletic Performance' => [
                'recommended' => [
                    'Protein Supplements' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Μυϊκή σύνθεση και ανάρρωση',
                        'forms' => ['whey isolate' => 9, 'whey concentrate' => 7, 'casein' => 8],
                        'dosage' => '20-40g μετά την προπόνηση',
                    ],
                    'Creatine' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Αυξάνει ATP, δύναμη και μυϊκή μάζα',
                        'forms' => ['monohydrate' => 9],
                        'dosage' => '3-5g ημερησίως',
                        'studies' => 'Πάνω από 500 μελέτες επιβεβαιώνουν αποτελεσματικότητα',
                    ],
                    'Magnesium' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Μυϊκή λειτουργία, αποκατάσταση, ενέργεια',
                        'dosage' => '300-400mg ημερησίως',
                    ],
                    'Electrolytes' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Αναπλήρωση απωλειών από εφίδρωση',
                        'dosage' => 'Κατά τη διάρκεια και μετά την άσκηση',
                    ],
                ],
                'contraindicated' => [],
                'caution' => [],
            ],

            'Cognitive Function' => [
                'recommended' => [
                    'Omega-3 & Fish Oil' => [
                        'evidence' => 'strong',
                        'mechanism' => 'DHA είναι δομικό συστατικό του εγκεφάλου',
                        'dosage' => '1-2g με υψηλό DHA',
                    ],
                    'B-Vitamins' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'B12, φολικό: υγεία νευρώνων, μείωση ομοκυστεΐνης',
                        'dosage' => 'B-Complex με μεθυλ-μορφές',
                    ],
                    'Magnesium' => [
                        'evidence' => 'moderate',
                        'mechanism' => 'Threonate περνά τον αιματοεγκεφαλικό φραγμό',
                        'forms' => ['threonate' => 10, 'glycinate' => 7],
                        'dosage' => '144mg elemental Mg από threonate',
                    ],
                ],
                'contraindicated' => [],
                'caution' => [],
            ],

            'Anemia' => [
                'recommended' => [
                    'Iron' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Απαραίτητο για τη σύνθεση αιμοσφαιρίνης',
                        'forms' => ['bisglycinate' => 9, 'ferrous sulfate' => 6, 'carbonyl' => 8],
                        'dosage' => '18-65mg elemental iron ανάλογα με τη σοβαρότητα',
                    ],
                    'Vitamin C' => [
                        'evidence' => 'strong',
                        'mechanism' => 'Αυξάνει την απορρόφηση σιδήρου έως 6 φορές',
                        'dosage' => '200mg μαζί με σίδηρο',
                    ],
                    'B-Vitamins' => [
                        'evidence' => 'strong',
                        'mechanism' => 'B12 και φολικό για μεγαλοβλαστική αναιμία',
                        'dosage' => 'B12: 1000mcg, Φολικό: 400-800mcg',
                    ],
                ],
                'contraindicated' => [],
                'caution' => ['Iron' => 'Μην παίρνετε χωρίς επιβεβαιωμένη ανεπάρκεια'],
            ],
        ];
    }

    /**
     * Bioavailability scores for different supplement forms
     */
    public static function getBioavailabilityData(): array
    {
        return [
            'Magnesium' => [
                'bisglycinate' => ['score' => 9, 'absorption' => '90%', 'notes' => 'Καλύτερη ανοχή, δεν προκαλεί διάρροια'],
                'glycinate' => ['score' => 9, 'absorption' => '90%', 'notes' => 'Ιδανικό για ύπνο και άγχος'],
                'taurate' => ['score' => 8, 'absorption' => '80%', 'notes' => 'Ιδανικό για καρδιά και πίεση'],
                'threonate' => ['score' => 9, 'absorption' => '85%', 'notes' => 'Περνά αιματοεγκεφαλικό φραγμό - για μνήμη'],
                'citrate' => ['score' => 7, 'absorption' => '65%', 'notes' => 'Καλή επιλογή, μπορεί να χαλαρώσει έντερο'],
                'malate' => ['score' => 7, 'absorption' => '60%', 'notes' => 'Καλό για ενέργεια και μυϊκούς πόνους'],
                'oxide' => ['score' => 3, 'absorption' => '4-20%', 'notes' => 'Φθηνό αλλά πολύ χαμηλή απορρόφηση'],
                'sulfate' => ['score' => 4, 'absorption' => '30%', 'notes' => 'Χρήσιμο για δυσκοιλιότητα'],
            ],
            'Vitamin D' => [
                'D3 cholecalciferol' => ['score' => 9, 'notes' => 'Ανθρώπινη μορφή, 2x πιο αποτελεσματική από D2'],
                'D2 ergocalciferol' => ['score' => 5, 'notes' => 'Φυτική μορφή, λιγότερο αποτελεσματική'],
            ],
            'Omega-3' => [
                'triglyceride (TG)' => ['score' => 9, 'absorption' => '70%', 'notes' => 'Φυσική μορφή, καλύτερη απορρόφηση'],
                'phospholipid' => ['score' => 9, 'notes' => 'Krill oil, εξαιρετική βιοδιαθεσιμότητα'],
                'ethyl ester (EE)' => ['score' => 6, 'absorption' => '40%', 'notes' => 'Πιο φθηνό αλλά χαμηλότερη απορρόφηση'],
            ],
            'CoQ10' => [
                'ubiquinol' => ['score' => 9, 'notes' => 'Ενεργή μορφή, ιδανική για >40 ετών'],
                'ubiquinone' => ['score' => 6, 'notes' => 'Πρέπει να μετατραπεί στο σώμα'],
            ],
            'Iron' => [
                'bisglycinate' => ['score' => 9, 'notes' => 'Εξαιρετική ανοχή, δεν προκαλεί δυσκοιλιότητα'],
                'ferrous sulfate' => ['score' => 6, 'notes' => 'Φθηνό αλλά συχνές παρενέργειες'],
                'carbonyl iron' => ['score' => 8, 'notes' => 'Καλή ανοχή, ασφαλές'],
                'ferric iron' => ['score' => 4, 'notes' => 'Χαμηλή απορρόφηση'],
            ],
            'Zinc' => [
                'picolinate' => ['score' => 9, 'absorption' => '60%', 'notes' => 'Καλύτερη απορρόφηση'],
                'citrate' => ['score' => 8, 'absorption' => '50%', 'notes' => 'Πολύ καλή επιλογή'],
                'gluconate' => ['score' => 7, 'absorption' => '40%', 'notes' => 'Συνηθισμένη μορφή'],
                'oxide' => ['score' => 4, 'absorption' => '20%', 'notes' => 'Φθηνό αλλά χαμηλή απορρόφηση'],
            ],
            'Calcium' => [
                'citrate' => ['score' => 9, 'notes' => 'Δεν χρειάζεται οξύ στομάχου, ιδανικό για ηλικιωμένους'],
                'hydroxyapatite' => ['score' => 8, 'notes' => 'Φυσική μορφή από οστά'],
                'carbonate' => ['score' => 7, 'notes' => 'Χρειάζεται φαγητό για απορρόφηση'],
            ],
            'B12' => [
                'methylcobalamin' => ['score' => 9, 'notes' => 'Ενεργή μορφή, ιδανική για MTHFR πολυμορφισμούς'],
                'adenosylcobalamin' => ['score' => 9, 'notes' => 'Ενεργή μορφή για μιτοχόνδρια'],
                'cyanocobalamin' => ['score' => 6, 'notes' => 'Συνθετική, πρέπει να μετατραπεί'],
            ],
            'Folate' => [
                'methylfolate (5-MTHF)' => ['score' => 9, 'notes' => 'Ενεργή μορφή, ιδανική για MTHFR'],
                'folic acid' => ['score' => 6, 'notes' => 'Συνθετική, μπορεί να μην μετατραπεί αποτελεσματικά'],
            ],
        ];
    }

    /**
     * Greek medical terminology translations
     */
    public static function getGreekTerminology(): array
    {
        return [
            // Conditions
            'diabetes' => 'διαβήτης',
            'hypertension' => 'υπέρταση',
            'cardiovascular' => 'καρδιαγγειακό',
            'arthritis' => 'αρθρίτιδα',
            'osteoporosis' => 'οστεοπόρωση',
            'anemia' => 'αναιμία',
            'anxiety' => 'άγχος',
            'insomnia' => 'αϋπνία',
            'fatigue' => 'κόπωση',
            'inflammation' => 'φλεγμονή',

            // Mechanisms
            'bioavailability' => 'βιοδιαθεσιμότητα',
            'absorption' => 'απορρόφηση',
            'efficacy' => 'αποτελεσματικότητα',
            'contraindication' => 'αντένδειξη',
            'side effect' => 'παρενέργεια',
            'dosage' => 'δοσολογία',

            // Forms
            'capsule' => 'κάψουλα',
            'softgel' => 'μαλακή κάψουλα',
            'tablet' => 'δισκίο',
            'powder' => 'σκόνη',
            'liquid' => 'υγρό',
            'gummy' => 'μασώμενο ζελεδάκι',

            // Scores
            'overall score' => 'συνολική βαθμολογία',
            'efficacy score' => 'βαθμολογία αποτελεσματικότητας',
            'quality score' => 'βαθμολογία ποιότητας',
            'value score' => 'βαθμολογία αξίας',
        ];
    }

    /**
     * Get evidence-based explanation for a condition-supplement pair
     */
    public static function getConditionExplanation(string $condition, string $category): ?array
    {
        $mappings = self::getConditionMappings();

        if (isset($mappings[$condition]['recommended'][$category])) {
            return $mappings[$condition]['recommended'][$category];
        }

        return null;
    }

    /**
     * Check for contraindications
     */
    public static function getContraindications(string $condition, string $category): ?string
    {
        $mappings = self::getConditionMappings();

        if (isset($mappings[$condition]['contraindicated'][$category])) {
            return $mappings[$condition]['contraindicated'][$category];
        }

        if (isset($mappings[$condition]['caution'][$category])) {
            return $mappings[$condition]['caution'][$category];
        }

        return null;
    }

    /**
     * Get bioavailability explanation for a form
     */
    public static function getBioavailabilityExplanation(string $category, string $form): ?array
    {
        $data = self::getBioavailabilityData();

        if (isset($data[$category])) {
            foreach ($data[$category] as $formName => $info) {
                if (stripos($formName, $form) !== false || stripos($form, $formName) !== false) {
                    return $info;
                }
            }
        }

        return null;
    }
}
