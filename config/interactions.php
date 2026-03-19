<?php

/**
 * Supplement Interaction Warnings
 *
 * Defines known interactions between supplements.
 * Used by the AI to warn patients when recommending multiple supplements.
 *
 * Types:
 * - 'avoid': Do not take together
 * - 'separate': Take at different times (e.g., 2 hours apart)
 * - 'monitor': Safe but monitor dosage/effects
 * - 'synergy': Beneficial to take together
 */

return [

    // =========================================================================
    // ABSORPTION CONFLICTS (take separately)
    // =========================================================================
    [
        'supplement_a' => 'calcium',
        'supplement_b' => 'iron',
        'type' => 'separate',
        'warning_el' => 'Το ασβέστιο μπλοκάρει την απορρόφηση του σιδήρου. Πάρτε τα σε διαφορετικές ώρες (τουλάχιστον 2 ώρες διαφορά).',
        'warning_en' => 'Calcium blocks iron absorption. Take at least 2 hours apart.',
    ],
    [
        'supplement_a' => 'calcium',
        'supplement_b' => 'zinc',
        'type' => 'separate',
        'warning_el' => 'Το ασβέστιο ανταγωνίζεται τον ψευδάργυρο για απορρόφηση. Πάρτε τα σε διαφορετικές ώρες.',
        'warning_en' => 'Calcium competes with zinc for absorption. Take at different times.',
    ],
    [
        'supplement_a' => 'calcium',
        'supplement_b' => 'magnesium',
        'type' => 'separate',
        'warning_el' => 'Υψηλές δόσεις ασβεστίου μπορούν να μειώσουν την απορρόφηση του μαγνησίου. Πάρτε τα σε διαφορετικά γεύματα.',
        'warning_en' => 'High-dose calcium can reduce magnesium absorption. Take with different meals.',
    ],
    [
        'supplement_a' => 'iron',
        'supplement_b' => 'zinc',
        'type' => 'separate',
        'warning_el' => 'Ο σίδηρος και ο ψευδάργυρος ανταγωνίζονται για απορρόφηση. Πάρτε τα σε διαφορετικές ώρες.',
        'warning_en' => 'Iron and zinc compete for absorption. Take at different times.',
    ],

    // =========================================================================
    // LONG-TERM DEPLETION RISKS (monitor)
    // =========================================================================
    [
        'supplement_a' => 'zinc',
        'supplement_b' => 'copper',
        'type' => 'monitor',
        'warning_el' => 'Μακροχρόνια λήψη ψευδαργύρου (>40mg/μέρα) εξαντλεί τον χαλκό. Προσθέστε 1-2mg χαλκού αν παίρνετε ψευδάργυρο μακροπρόθεσμα.',
        'warning_en' => 'Long-term zinc (>40mg/day) depletes copper. Add 1-2mg copper if taking zinc long-term.',
    ],
    [
        'supplement_a' => 'vitamin-d',
        'supplement_b' => 'vitamin-k2',
        'type' => 'synergy',
        'warning_el' => 'Η βιταμίνη D αυξάνει την απορρόφηση ασβεστίου. Η K2 κατευθύνει το ασβέστιο στα οστά αντί στις αρτηρίες. Πάρτε τα μαζί.',
        'warning_en' => 'Vitamin D increases calcium absorption. K2 directs calcium to bones instead of arteries. Take together.',
    ],
    [
        'supplement_a' => 'vitamin-d',
        'supplement_b' => 'magnesium',
        'type' => 'synergy',
        'warning_el' => 'Το μαγνήσιο απαιτείται για την ενεργοποίηση της βιταμίνης D. Χωρίς αρκετό μαγνήσιο, η βιταμίνη D δεν μετατρέπεται στην ενεργή μορφή.',
        'warning_en' => 'Magnesium is required to activate vitamin D. Without enough magnesium, vitamin D cannot convert to its active form.',
    ],

    // =========================================================================
    // BENEFICIAL COMBINATIONS (synergy)
    // =========================================================================
    [
        'supplement_a' => 'iron',
        'supplement_b' => 'vitamin-c',
        'type' => 'synergy',
        'warning_el' => 'Η βιταμίνη C αυξάνει δραματικά την απορρόφηση του σιδήρου (έως 6x). Πάρτε τα μαζί.',
        'warning_en' => 'Vitamin C dramatically increases iron absorption (up to 6x). Take together.',
    ],
    [
        'supplement_a' => 'curcumin',
        'supplement_b' => 'piperine',
        'type' => 'synergy',
        'warning_el' => 'Η πιπερίνη (μαύρο πιπέρι) αυξάνει την απορρόφηση της κουρκουμίνης κατά 2000%. Ψάξτε προϊόντα με BioPerine.',
        'warning_en' => 'Piperine (black pepper) increases curcumin absorption by 2000%. Look for products with BioPerine.',
    ],
    [
        'supplement_a' => 'coq10',
        'supplement_b' => 'omega-3',
        'type' => 'synergy',
        'warning_el' => 'Το CoQ10 είναι λιποδιαλυτό και απορροφάται καλύτερα με λιπαρά. Πάρτε το μαζί με ωμέγα-3 ή με φαγητό που περιέχει λίπος.',
        'warning_en' => 'CoQ10 is fat-soluble and absorbs better with fats. Take with omega-3 or a fat-containing meal.',
    ],
    [
        'supplement_a' => 'collagen',
        'supplement_b' => 'vitamin-c',
        'type' => 'synergy',
        'warning_el' => 'Η βιταμίνη C είναι απαραίτητη για τη σύνθεση κολλαγόνου στο σώμα. Πάρτε τα μαζί για μέγιστο αποτέλεσμα.',
        'warning_en' => 'Vitamin C is essential for collagen synthesis in the body. Take together for maximum benefit.',
    ],

    // =========================================================================
    // TIMING CONFLICTS
    // =========================================================================
    [
        'supplement_a' => 'magnesium',
        'supplement_b' => 'antibiotics',
        'type' => 'separate',
        'warning_el' => 'Το μαγνήσιο μπορεί να μειώσει την αποτελεσματικότητα ορισμένων αντιβιοτικών (τετρακυκλίνες, φθοροκινολόνες). Πάρτε τα 2 ώρες πριν ή 4-6 ώρες μετά.',
        'warning_en' => 'Magnesium can reduce effectiveness of certain antibiotics (tetracyclines, fluoroquinolones). Take 2 hours before or 4-6 hours after.',
    ],
    [
        'supplement_a' => 'iron',
        'supplement_b' => 'thyroid-medication',
        'type' => 'separate',
        'warning_el' => 'Ο σίδηρος μειώνει την απορρόφηση θυρεοειδικών φαρμάκων (λεβοθυροξίνη). Πάρτε τα τουλάχιστον 4 ώρες χωριστά.',
        'warning_en' => 'Iron reduces absorption of thyroid medications (levothyroxine). Take at least 4 hours apart.',
    ],
    [
        'supplement_a' => 'calcium',
        'supplement_b' => 'thyroid-medication',
        'type' => 'separate',
        'warning_el' => 'Το ασβέστιο μειώνει την απορρόφηση θυρεοειδικών φαρμάκων. Πάρτε τα τουλάχιστον 4 ώρες χωριστά.',
        'warning_en' => 'Calcium reduces thyroid medication absorption. Take at least 4 hours apart.',
    ],

    // =========================================================================
    // EXCESS/OVERDOSE RISKS
    // =========================================================================
    [
        'supplement_a' => 'vitamin-d',
        'supplement_b' => 'calcium',
        'type' => 'monitor',
        'warning_el' => 'Η βιταμίνη D αυξάνει την απορρόφηση ασβεστίου. Αν παίρνετε και τα δύο, προσέξτε μην υπερβείτε τα 2500mg ασβεστίου/ημέρα (κίνδυνος υπερασβεστιαιμίας).',
        'warning_en' => 'Vitamin D increases calcium absorption. If taking both, ensure total calcium does not exceed 2500mg/day (hypercalcemia risk).',
    ],
    [
        'supplement_a' => 'omega-3',
        'supplement_b' => 'blood-thinners',
        'type' => 'avoid',
        'warning_el' => 'Τα ωμέγα-3 σε υψηλές δόσεις (>3g/ημέρα) έχουν αντιαιμοπεταλιακή δράση. Αν παίρνετε αντιπηκτικά (βαρφαρίνη, ασπιρίνη), συμβουλευτείτε τον γιατρό σας.',
        'warning_en' => 'High-dose omega-3 (>3g/day) has antiplatelet effects. If taking blood thinners (warfarin, aspirin), consult your doctor.',
    ],
    [
        'supplement_a' => 'vitamin-e',
        'supplement_b' => 'blood-thinners',
        'type' => 'avoid',
        'warning_el' => 'Η βιταμίνη E σε υψηλές δόσεις μπορεί να αυξήσει τον κίνδυνο αιμορραγίας αν συνδυαστεί με αντιπηκτικά. Συμβουλευτείτε τον γιατρό σας.',
        'warning_en' => 'High-dose vitamin E may increase bleeding risk when combined with blood thinners. Consult your doctor.',
    ],
    [
        'supplement_a' => 'melatonin',
        'supplement_b' => 'sedatives',
        'type' => 'avoid',
        'warning_el' => 'Η μελατονίνη μπορεί να ενισχύσει τη δράση ηρεμιστικών φαρμάκων. Μην συνδυάζετε χωρίς ιατρική συμβουλή.',
        'warning_en' => 'Melatonin may enhance effects of sedative medications. Do not combine without medical advice.',
    ],
    [
        'supplement_a' => 'st-johns-wort',
        'supplement_b' => 'antidepressants',
        'type' => 'avoid',
        'warning_el' => 'Το βαλσαμόχορτο (St. John\'s Wort) αλληλεπιδρά σοβαρά με αντικαταθλιπτικά (SSRI). Κίνδυνος σεροτονινεργικού συνδρόμου. ΠΟΤΕ μην τα συνδυάσετε.',
        'warning_en' => 'St. John\'s Wort seriously interacts with antidepressants (SSRIs). Risk of serotonin syndrome. NEVER combine.',
    ],
];
