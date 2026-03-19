<?php

/**
 * Clinical Reference Data for Supplement Scoring
 *
 * This config drives the entire quality analysis engine.
 * Each category defines:
 * - therapeutic_range: clinically studied effective dose range
 * - preferred_forms: forms with superior bioavailability (score 0.0-1.0)
 * - penalized_forms: forms with poor bioavailability or quality concerns
 * - red_flags: patterns that indicate a low-quality product
 * - key_ingredients: regex patterns to match primary active ingredients in active_ingredients JSON
 * - cofactors: beneficial companion ingredients that improve efficacy
 * - notes: clinical context for AI recommendations
 */

return [

    // =========================================================================
    // 1. OMEGA-3 & FISH OIL
    // =========================================================================
    'omega-3-fish-oil' => [
        'therapeutic_range' => [
            'min' => 500,   // mg combined EPA+DHA minimum for general health
            'max' => 3000,  // mg upper safe limit
            'optimal' => 2000,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'triglyceride' => 1.0,
            'triglyceride form' => 1.0,
            're-esterified triglyceride' => 1.0,
            'rtg' => 1.0,
            'phospholipid' => 0.95,
            'krill oil' => 0.90,
            'krill' => 0.90,
            'algal' => 0.85,         // vegan source, good DHA
            'algae' => 0.85,
            'ethyl ester' => 0.55,   // cheaper, lower absorption
        ],
        'penalized_forms' => [
            'ethyl ester' => -1.5,
            'concentrate' => -0.5,   // often EE form
        ],
        'red_flags' => [
            'no epa/dha breakdown',
            'proprietary blend',
            'total omega-3 only',     // hides EPA/DHA ratio
        ],
        'key_ingredients' => ['epa', 'dha', 'omega-3', 'fish oil', 'omega 3', 'ιχθυέλαιο', 'ωμέγα-3', 'ωμέγα 3'],
        'cofactors' => ['vitamin e', 'astaxanthin', 'vitamin d'],
        'notes' => 'EPA for inflammation/mood, DHA for brain/eyes. Triglyceride form absorbs 70% better than ethyl ester. Always check combined EPA+DHA, not just "fish oil" amount.',
    ],

    // =========================================================================
    // 2. MAGNESIUM
    // =========================================================================
    'magnesium' => [
        'therapeutic_range' => [
            'min' => 200,   // mg elemental magnesium
            'max' => 500,
            'optimal' => 400,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'bisglycinate' => 1.0,
            'glycinate' => 1.0,
            'l-threonate' => 0.95,   // brain-specific, crosses BBB
            'threonate' => 0.95,
            'taurate' => 0.90,       // heart-specific
            'malate' => 0.85,        // energy/muscle
            'orotate' => 0.80,       // cardiovascular
            'citrate' => 0.75,       // good general, mild laxative
        ],
        'penalized_forms' => [
            'oxide' => -2.5,         // 4% absorption, essentially useless
            'carbonate' => -1.5,
            'hydroxide' => -2.0,     // antacid, not supplement
            'sulfate' => -1.5,       // epsom salt, poor oral absorption
        ],
        'red_flags' => [
            'magnesium oxide.*high absorption',
            'magnesium oxide.*premium',
            'magnesium oxide.*advanced',
            'proprietary blend',
        ],
        'key_ingredients' => ['magnesium', 'μαγνήσιο'],
        'cofactors' => ['vitamin b6', 'taurine', 'vitamin d'],
        'notes' => 'Oxide is 4% absorbed — a 500mg oxide tablet delivers ~20mg. Glycinate is 80%+ absorbed and gentle on stomach. Threonate specifically for cognitive support.',
    ],

    // =========================================================================
    // 3. VITAMIN D
    // =========================================================================
    'vitamin-d' => [
        'therapeutic_range' => [
            'min' => 1000,  // IU for maintenance
            'max' => 10000, // IU upper safe limit
            'optimal' => 4000,
            'unit' => 'IU',
        ],
        'preferred_forms' => [
            'd3' => 1.0,
            'cholecalciferol' => 1.0,
            'calcifediol' => 0.95,   // 25-OH-D3, already partially converted
        ],
        'penalized_forms' => [
            'd2' => -2.0,            // ergocalciferol, ~30% less effective
            'ergocalciferol' => -2.0,
        ],
        'red_flags' => [
            'vitamin d2.*premium',
            'ergocalciferol.*high potency',
        ],
        'key_ingredients' => ['vitamin d', 'cholecalciferol', 'ergocalciferol', 'd3', 'd2', 'βιταμίνη d', 'βιταμίνη d3'],
        'cofactors' => ['vitamin k2', 'k2', 'mk-7', 'mk-4', 'magnesium', 'calcium'],
        'notes' => 'D3 is 87% more effective at raising blood levels than D2. Always pair with K2 (MK-7) to direct calcium to bones, not arteries. Take with fat for absorption.',
    ],

    // =========================================================================
    // 4. PROBIOTICS
    // =========================================================================
    'probiotics' => [
        'therapeutic_range' => [
            'min' => 1,       // billion CFU
            'max' => 100,
            'optimal' => 20,
            'unit' => 'billion CFU',
        ],
        'preferred_forms' => [
            'delayed release' => 1.0,
            'delayed-release' => 1.0,
            'acid-resistant' => 0.95,
            'enteric coated' => 0.95,
            'enteric-coated' => 0.95,
            'microencapsulated' => 0.90,
            'shelf stable' => 0.80,
            'shelf-stable' => 0.80,
            'freeze-dried' => 0.75,
        ],
        'penalized_forms' => [
            'heat-treated' => -2.0,   // dead bacteria, no probiotic benefit
            'tyndallized' => -1.0,    // debatable efficacy
        ],
        'red_flags' => [
            'proprietary blend',
            'cfu at time of manufacture',  // should be at expiry
            'no strain identification',
        ],
        'key_ingredients' => ['lactobacillus', 'bifidobacterium', 'saccharomyces', 'probiotic', 'cfu', 'bacillus', 'προβιοτικ'],
        'cofactors' => ['prebiotic', 'fos', 'inulin', 'gos'],
        'notes' => 'Strain-specific benefits matter more than CFU count. Look for strains with clinical studies (e.g., LGG, BB-12). CFU should be guaranteed at expiry, not manufacture.',
    ],

    // =========================================================================
    // 5. COLLAGEN
    // =========================================================================
    'collagen' => [
        'therapeutic_range' => [
            'min' => 2500,   // mg
            'max' => 15000,
            'optimal' => 10000,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'hydrolyzed' => 1.0,
            'peptides' => 1.0,
            'hydrolysate' => 0.95,
            'verisol' => 1.0,        // patented, clinically studied
            'fortigel' => 0.95,      // joint-specific peptides
            'naticol' => 0.90,
            'type i' => 0.85,
            'type ii' => 0.85,       // joint-specific (UC-II)
            'type iii' => 0.85,
            'uc-ii' => 0.95,         // undenatured type II, only needs 40mg
        ],
        'penalized_forms' => [
            'gelatin' => -1.5,       // not hydrolyzed, poor absorption
            'unhydrolyzed' => -2.0,
        ],
        'red_flags' => [
            'proprietary blend',
            'collagen.*gummy',        // usually underdosed in gummies
        ],
        'key_ingredients' => ['collagen', 'peptides', 'hydrolyzed', 'κολλαγόνο'],
        'cofactors' => ['vitamin c', 'hyaluronic acid', 'biotin'],
        'notes' => 'Hydrolyzed peptides are 90%+ absorbed vs intact collagen. Type I/III for skin/hair, Type II for joints. UC-II is special — only needs 40mg undenatured form.',
    ],

    // =========================================================================
    // 6. PROTEIN SUPPLEMENTS
    // =========================================================================
    'protein-supplements' => [
        'therapeutic_range' => [
            'min' => 20,    // g per serving
            'max' => 50,
            'optimal' => 30,
            'unit' => 'g',
        ],
        'preferred_forms' => [
            'isolate' => 1.0,        // highest purity, ~90% protein
            'hydrolysate' => 0.95,   // pre-digested, fastest absorption
            'hydrolyzed' => 0.95,
            'micellar casein' => 0.85, // slow release
            'grass-fed' => 0.80,
            'whey' => 0.75,
            'concentrate' => 0.65,   // lower purity ~80%
        ],
        'penalized_forms' => [
            'amino spiking' => -3.0,
            'proprietary protein blend' => -2.0,
            'soy protein isolate' => -0.5,
        ],
        'red_flags' => [
            'amino spiking',
            'proprietary protein blend',
            'added creatine.*protein',   // may indicate amino spiking
            'added taurine.*protein',
        ],
        'key_ingredients' => ['protein', 'whey', 'casein', 'pea protein', 'rice protein', 'πρωτεΐνη'],
        'cofactors' => ['digestive enzymes', 'protease', 'lactase'],
        'notes' => 'Check protein per calorie ratio. Isolate > Concentrate for purity. Watch for amino spiking (added cheap aminos to inflate protein count).',
    ],

    // =========================================================================
    // 7. VITAMIN C
    // =========================================================================
    'vitamin-c' => [
        'therapeutic_range' => [
            'min' => 250,   // mg
            'max' => 2000,
            'optimal' => 1000,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'liposomal' => 1.0,
            'sodium ascorbate' => 0.90,    // buffered, gentle
            'calcium ascorbate' => 0.85,
            'ester-c' => 0.85,
            'ascorbyl palmitate' => 0.80,  // fat-soluble form
            'buffered' => 0.80,
            'ascorbic acid' => 0.70,       // effective but acidic
            'time-release' => 0.75,
        ],
        'penalized_forms' => [
            'synthetic.*filler' => -1.0,
        ],
        'red_flags' => [
            'proprietary blend',
            'vitamin c.*gummy.*1000',  // hard to fit 1000mg in a gummy
        ],
        'key_ingredients' => ['vitamin c', 'ascorbic acid', 'ascorbate', 'ester-c', 'βιταμίνη c'],
        'cofactors' => ['bioflavonoids', 'quercetin', 'rose hips', 'zinc', 'citrus bioflavonoids'],
        'notes' => 'Above 200mg, absorption drops. Split doses or use liposomal for high doses. Buffered forms gentler on stomach.',
    ],

    // =========================================================================
    // 8. CoQ10
    // =========================================================================
    'coq10' => [
        'therapeutic_range' => [
            'min' => 100,   // mg
            'max' => 600,
            'optimal' => 200,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'ubiquinol' => 1.0,          // active/reduced form, 2-8x better absorption
            'kaneka ubiquinol' => 1.0,   // gold standard
            'kaneka' => 0.95,
            'ubiquinone' => 0.55,        // must be converted, lower absorption
        ],
        'penalized_forms' => [
            'ubiquinone.*powder' => -1.0, // worst form, needs fat
        ],
        'red_flags' => [
            'ubiquinone.*premium',
            'proprietary blend',
        ],
        'key_ingredients' => ['coq10', 'ubiquinol', 'ubiquinone', 'coenzyme q10', 'συνένζυμο q10'],
        'cofactors' => ['pqq', 'vitamin e', 'omega-3', 'bioperine', 'piperine'],
        'notes' => 'Ubiquinol is the active form — especially important after age 40 when conversion from ubiquinone declines. Always take with fat. Essential if on statins.',
    ],

    // =========================================================================
    // 9. B-VITAMINS
    // =========================================================================
    'b-vitamins' => [
        'therapeutic_range' => [
            'min' => 400,    // mcg folate equivalent as reference
            'max' => 1000,
            'optimal' => 800,
            'unit' => 'mcg DFE',
        ],
        'preferred_forms' => [
            'methylcobalamin' => 1.0,         // active B12
            'methyl b12' => 1.0,
            'adenosylcobalamin' => 0.95,
            'hydroxocobalamin' => 0.90,
            'methylfolate' => 1.0,            // active folate
            '5-mthf' => 1.0,
            'l-methylfolate' => 1.0,
            'quatrefolic' => 1.0,             // patented 5-MTHF
            'folinic acid' => 0.85,
            'pyridoxal-5-phosphate' => 0.95,  // active B6
            'p-5-p' => 0.95,
            'p5p' => 0.95,
            'riboflavin-5-phosphate' => 0.90, // active B2
            'benfotiamine' => 0.90,           // fat-soluble B1
            'pantethine' => 0.90,             // active B5
        ],
        'penalized_forms' => [
            'cyanocobalamin' => -1.5,     // cheap B12, requires conversion, releases cyanide
            'folic acid' => -1.5,         // synthetic folate, ~40% of population has MTHFR issues
            'pyridoxine hcl' => -0.5,     // cheap B6
        ],
        'red_flags' => [
            'folic acid.*methylfolate',   // mixing good and bad forms
            'cyanocobalamin.*premium',
            'proprietary blend',
            'folic acid.*prenatal',       // prenatal should always use methylfolate
        ],
        'key_ingredients' => ['b12', 'b-12', 'folate', 'folic', 'b6', 'b1', 'b2', 'b-complex', 'b complex', 'thiamin', 'riboflavin', 'niacin', 'biotin', 'pantothenic', 'βιταμίνη b', 'σύμπλεγμα β', 'φολικό'],
        'cofactors' => [],
        'notes' => '~40% of population has MTHFR polymorphism and cannot efficiently convert folic acid to active folate. Methylated forms bypass this. Cyanocobalamin releases trace cyanide during conversion.',
    ],

    // =========================================================================
    // 10. ZINC
    // =========================================================================
    'zinc' => [
        'therapeutic_range' => [
            'min' => 15,    // mg
            'max' => 50,
            'optimal' => 30,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'picolinate' => 1.0,
            'zinc picolinate' => 1.0,
            'bisglycinate' => 0.95,
            'zinc bisglycinate' => 0.95,
            'glycinate' => 0.90,
            'citrate' => 0.80,
            'zinc citrate' => 0.80,
            'gluconate' => 0.75,
            'acetate' => 0.70,       // good for lozenges
            'monomethionine' => 0.85, // OptiZinc
            'optizinc' => 0.85,
        ],
        'penalized_forms' => [
            'oxide' => -2.0,         // very poor absorption
            'zinc oxide' => -2.0,
            'sulfate' => -1.0,       // GI irritation
        ],
        'red_flags' => [
            'zinc oxide.*premium',
            'zinc oxide.*high absorption',
            'proprietary blend',
        ],
        'key_ingredients' => ['zinc', 'ψευδάργυρος', 'ψευδαργύρου'],
        'cofactors' => ['copper', 'vitamin c', 'quercetin'],
        'notes' => 'Zinc depletes copper — long-term use above 40mg/day should include 1-2mg copper. Picolinate is best absorbed. Take away from iron and calcium.',
    ],

    // =========================================================================
    // 11. VITAMIN A
    // =========================================================================
    'vitamin-a' => [
        'therapeutic_range' => [
            'min' => 2500,  // IU
            'max' => 10000,
            'optimal' => 5000,
            'unit' => 'IU',
        ],
        'preferred_forms' => [
            'retinyl palmitate' => 0.90,
            'retinol' => 0.85,
            'beta-carotene' => 0.75,    // must convert, variable efficiency
            'mixed carotenoids' => 0.80,
            'retinyl acetate' => 0.80,
        ],
        'penalized_forms' => [
            'beta-carotene only' => -0.5, // conversion varies 3-28x between people
        ],
        'red_flags' => [
            'proprietary blend',
            'beta-carotene.*complete vitamin a', // misleading if sole source
        ],
        'key_ingredients' => ['vitamin a', 'retinol', 'retinyl', 'beta-carotene', 'beta carotene', 'βιταμίνη a', 'ρετινόλη'],
        'cofactors' => ['vitamin d', 'vitamin e', 'zinc'],
        'notes' => 'Preformed vitamin A (retinol/retinyl) is directly usable. Beta-carotene conversion is highly variable. Excess preformed A is toxic — respect upper limits.',
    ],

    // =========================================================================
    // 12. CALCIUM
    // =========================================================================
    'calcium' => [
        'therapeutic_range' => [
            'min' => 500,   // mg (supplement, not total dietary)
            'max' => 1200,
            'optimal' => 600,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'calcium citrate' => 1.0,     // absorbed without stomach acid
            'citrate' => 1.0,
            'calcium malate' => 0.90,
            'calcium hydroxyapatite' => 0.90, // bone-derived, includes phosphorus
            'microcrystalline hydroxyapatite' => 0.90,
            'mcha' => 0.90,
            'algae calcium' => 0.85,      // plant-sourced
            'aquamin' => 0.85,
        ],
        'penalized_forms' => [
            'calcium carbonate' => -1.5,  // needs stomach acid, poorly absorbed
            'carbonate' => -1.5,
            'oyster shell' => -2.0,       // heavy metal contamination risk
            'coral calcium' => -2.0,
            'dolomite' => -2.5,           // lead contamination risk
        ],
        'red_flags' => [
            'calcium carbonate.*high absorption',
            'coral calcium',
            'oyster shell',
            'dolomite',
            'proprietary blend',
        ],
        'key_ingredients' => ['calcium', 'ασβέστιο'],
        'cofactors' => ['vitamin d', 'vitamin d3', 'vitamin k2', 'mk-7', 'magnesium'],
        'notes' => 'Calcium without D3 and K2 is incomplete — K2 directs calcium to bones instead of arteries. Citrate absorbs on empty stomach; carbonate needs food/acid. Split doses (max 500mg at a time).',
    ],

    // =========================================================================
    // 13. IRON
    // =========================================================================
    'iron' => [
        'therapeutic_range' => [
            'min' => 18,    // mg for menstruating women
            'max' => 65,
            'optimal' => 25,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'iron bisglycinate' => 1.0,
            'ferrous bisglycinate' => 1.0,
            'bisglycinate' => 1.0,
            'glycinate' => 0.95,
            'carbonyl iron' => 0.85,     // very safe, good absorption
            'iron protein succinylate' => 0.85,
            'ferrous fumarate' => 0.70,
            'ferrous gluconate' => 0.65,
            'polysaccharide iron' => 0.60,
        ],
        'penalized_forms' => [
            'ferrous sulfate' => -1.5,   // GI side effects, oxidative stress
            'ferric iron' => -1.0,       // poorly absorbed
        ],
        'red_flags' => [
            'ferrous sulfate.*gentle',   // it's not gentle
            'proprietary blend',
        ],
        'key_ingredients' => ['iron', 'ferrous', 'ferric', 'carbonyl iron', 'σίδηρος', 'σιδήρου'],
        'cofactors' => ['vitamin c', 'folate', 'b12', 'copper'],
        'notes' => 'Bisglycinate is 4x better absorbed than sulfate with almost no GI side effects. Always take with Vitamin C. Avoid taking with calcium, zinc, or tea/coffee.',
    ],

    // =========================================================================
    // 14. DIGESTIVE ENZYMES
    // =========================================================================
    'digestive-enzymes' => [
        'therapeutic_range' => [
            'min' => 10000,  // USP units (lipase as reference)
            'max' => 100000,
            'optimal' => 25000,
            'unit' => 'USP',
        ],
        'preferred_forms' => [
            'full spectrum' => 1.0,
            'broad spectrum' => 1.0,
            'plant-based' => 0.90,
            'fungal-derived' => 0.85,
            'pancreatin' => 0.80,
            'pancrelipase' => 0.85,
        ],
        'penalized_forms' => [
            'single enzyme' => -0.5,  // limited benefit
        ],
        'red_flags' => [
            'proprietary blend',
            'no enzyme activity units',  // must list activity, not mg
        ],
        'key_ingredients' => ['protease', 'lipase', 'amylase', 'bromelain', 'papain', 'cellulase', 'lactase', 'dpp-iv', 'πεπτικά ένζυμα'],
        'cofactors' => ['betaine hcl', 'ox bile', 'ginger', 'peppermint'],
        'notes' => 'Enzyme potency is measured in activity units (USP, FIP, HUT), NOT milligrams. A product listing only mg is a red flag. Look for at least protease + lipase + amylase.',
    ],

    // =========================================================================
    // 15. AMINO ACIDS
    // =========================================================================
    'amino-acids' => [
        'therapeutic_range' => [
            'min' => 500,   // mg primary amino acid
            'max' => 10000,
            'optimal' => 3000,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'l-form' => 1.0,         // natural, bioavailable
            'free form' => 0.95,     // not bound, faster absorption
            'free-form' => 0.95,
            'fermented' => 0.90,
            'ajinomoto' => 0.95,     // gold standard purity
            'kyowa' => 0.95,         // gold standard purity (Kyowa Hakko)
        ],
        'penalized_forms' => [
            'dl-form' => -1.5,       // racemic mix, 50% wrong chirality
            'd-form' => -2.0,        // wrong chirality, not bioactive
        ],
        'red_flags' => [
            'dl-methionine',
            'dl-phenylalanine.*sole',
            'proprietary blend',
        ],
        'key_ingredients' => ['l-glutamine', 'l-theanine', 'l-tyrosine', 'l-carnitine', 'bcaa', 'eaa', 'taurine', 'glycine', 'nac', 'n-acetyl cysteine', 'αμινοξ', 'γλυκίνη', 'ταυρίνη'],
        'cofactors' => ['vitamin b6', 'vitamin c', 'magnesium'],
        'notes' => 'Always L-form for biological activity. Free-form absorbs fastest. Ajinomoto and Kyowa Hakko are premium purity sources.',
    ],

    // =========================================================================
    // 16. ELECTROLYTES
    // =========================================================================
    'electrolytes' => [
        'therapeutic_range' => [
            'min' => 200,   // mg sodium as reference
            'max' => 1500,
            'optimal' => 1000,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'citrate' => 1.0,
            'chelated' => 0.95,
            'glycinate' => 0.90,
            'whole food' => 0.85,
            'coconut water' => 0.80,
        ],
        'penalized_forms' => [
            'oxide' => -1.5,
            'carbonate' => -1.0,
            'added sugar' => -2.0,
        ],
        'red_flags' => [
            'added sugar.*electrolyte',
            'artificial colors',
            'high fructose',
            'proprietary blend',
        ],
        'key_ingredients' => ['sodium', 'potassium', 'magnesium', 'chloride', 'calcium', 'phosphorus', 'ηλεκτρολύτ', 'κάλιο', 'νάτριο'],
        'cofactors' => ['vitamin c', 'b vitamins'],
        'notes' => 'Key ratio: sodium + potassium + magnesium. Avoid products with excessive sugar. Look for balanced mineral profile, not just sodium.',
    ],

    // =========================================================================
    // 17. JOINT SUPPORT
    // =========================================================================
    'joint-support' => [
        'therapeutic_range' => [
            'min' => 1000,  // mg glucosamine as reference
            'max' => 3000,
            'optimal' => 1500,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'glucosamine sulfate' => 1.0,       // best studied form
            'glucosamine hydrochloride' => 0.80,
            'chondroitin sulfate' => 0.90,
            'uc-ii' => 0.95,                    // undenatured type II collagen
            'undenatured type ii' => 0.95,
            'msm' => 0.85,
            'hyaluronic acid' => 0.85,
            'boswellia' => 0.85,
            'boswellic acid' => 0.85,
            'akba' => 0.90,                     // most active boswellic acid
        ],
        'penalized_forms' => [
            'glucosamine hcl.*only' => -0.5,
            'shellfish.*allergy' => -0.5,       // potential allergen issue
        ],
        'red_flags' => [
            'proprietary blend',
            'underdosed glucosamine',
        ],
        'key_ingredients' => ['glucosamine', 'chondroitin', 'msm', 'hyaluronic', 'boswellia', 'collagen type ii', 'uc-ii', 'turmeric', 'curcumin', 'γλυκοζαμίνη', 'χονδροϊτίνη', 'υαλουρονικ', 'αρθρ', 'κουρκουμίνη'],
        'cofactors' => ['vitamin c', 'manganese', 'vitamin d'],
        'notes' => 'Glucosamine sulfate is the most studied form (1500mg/day). UC-II needs only 40mg. Combination of glucosamine + chondroitin + MSM is common clinical approach.',
    ],

    // =========================================================================
    // 18. SLEEP SUPPORT
    // =========================================================================
    'sleep-support' => [
        'therapeutic_range' => [
            'min' => 200,   // mg magnesium or 1mg melatonin as reference
            'max' => 500,
            'optimal' => 400,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'magnesium glycinate' => 1.0,
            'magnesium bisglycinate' => 1.0,
            'magnesium threonate' => 0.95,
            'l-theanine' => 0.90,
            'glycine' => 0.85,
            'apigenin' => 0.85,
            'pharmagaba' => 0.90,        // patented GABA form
            'suntheanine' => 0.95,       // patented L-theanine
        ],
        'penalized_forms' => [
            'melatonin.*10mg' => -1.0,   // overdosed, 0.3-1mg is clinical dose
            'diphenhydramine' => -3.0,   // antihistamine, not a supplement
            'magnesium oxide' => -2.0,
        ],
        'red_flags' => [
            'melatonin.*high dose',
            'proprietary blend',
            'artificial colors.*sleep',
        ],
        'key_ingredients' => ['melatonin', 'magnesium', 'l-theanine', 'gaba', 'glycine', 'valerian', 'passionflower', 'chamomile', 'apigenin', 'tryptophan', '5-htp', 'μελατονίνη', 'ύπνο', 'βαλεριάνα', 'χαμομήλι'],
        'cofactors' => ['vitamin b6', 'magnesium', 'zinc'],
        'notes' => 'Melatonin clinical dose is 0.3-1mg, NOT 5-10mg. Higher doses cause morning grogginess. Magnesium glycinate + L-theanine + glycine is evidence-based non-hormonal stack.',
    ],

    // =========================================================================
    // 19. HERBAL/ADAPTOGENS
    // =========================================================================
    'herbal-adaptogens' => [
        'therapeutic_range' => [
            'min' => 300,   // mg primary extract
            'max' => 2000,
            'optimal' => 600,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'ksm-66' => 1.0,            // gold standard ashwagandha
            'sensoril' => 0.95,         // patented ashwagandha
            'shoden' => 0.95,           // high-withanolide ashwagandha
            'standardized extract' => 0.90,
            'full spectrum extract' => 0.85,
            'root extract' => 0.80,
            'bacognize' => 0.95,        // patented bacopa
            'synapsa' => 0.90,          // patented bacopa
            'cereboost' => 0.90,        // patented ginseng
            'rhodiola.*3%' => 0.90,     // standardized rosavins
        ],
        'penalized_forms' => [
            'raw powder' => -1.0,       // unextracted, low potency
            'whole herb.*powder' => -1.0,
            'root powder' => -0.5,      // less potent than extract
        ],
        'red_flags' => [
            'proprietary blend',
            'ashwagandha.*leaf.*only',   // should be root, not leaf
            'no standardization',
        ],
        'key_ingredients' => ['ashwagandha', 'rhodiola', 'ginseng', 'panax', 'bacopa', 'lion\'s mane', 'reishi', 'cordyceps', 'holy basil', 'tulsi', 'eleuthero', 'maca', 'astragalus', 'milk thistle', 'silymarin', 'turmeric', 'curcumin', 'ασβαγκάντα', 'γκίνσενγκ', 'γαϊδουράγκαθο', 'κουρκουμάς'],
        'cofactors' => ['bioperine', 'piperine', 'black pepper'],
        'notes' => 'Standardized extracts with verified active compound percentages are essential. Raw powders are much less potent. Patented extracts (KSM-66, Sensoril) have clinical trial backing.',
    ],

    // =========================================================================
    // 20. FIBER SUPPLEMENTS
    // =========================================================================
    'fiber-supplements' => [
        'therapeutic_range' => [
            'min' => 3,     // g per serving
            'max' => 15,
            'optimal' => 7,
            'unit' => 'g',
        ],
        'preferred_forms' => [
            'psyllium husk' => 1.0,          // most studied, soluble + insoluble
            'organic psyllium' => 1.0,
            'acacia fiber' => 0.90,          // gentle, prebiotic
            'sunfiber' => 0.90,              // patented guar gum
            'partially hydrolyzed guar gum' => 0.90,
            'inulin' => 0.80,               // prebiotic
            'glucomannan' => 0.85,
            'beta-glucan' => 0.85,
            'oat fiber' => 0.75,
        ],
        'penalized_forms' => [
            'methylcellulose' => -0.5,       // synthetic
            'polydextrose' => -1.0,          // synthetic, minimal benefit
            'wheat dextrin' => -0.5,
        ],
        'red_flags' => [
            'added sugar.*fiber',
            'artificial sweetener.*fiber',
            'proprietary blend',
        ],
        'key_ingredients' => ['psyllium', 'fiber', 'inulin', 'glucomannan', 'pectin', 'cellulose', 'acacia', 'guar', 'beta-glucan', 'flaxseed', 'φυτικές ίνες', 'ψύλλιο'],
        'cofactors' => ['probiotics', 'prebiotics'],
        'notes' => 'Psyllium is the gold standard — soluble + insoluble, cholesterol-lowering. Start low, increase slowly. Always take with plenty of water.',
    ],

    // =========================================================================
    // 21. MULTIVITAMINS
    // =========================================================================
    'multivitamins' => [
        'therapeutic_range' => [
            'min' => 400,
            'max' => 1000,
            'optimal' => 800,
            'unit' => 'mcg DFE (folate reference)',
        ],
        'preferred_forms' => [
            'methylfolate' => 1.0, 'methylcobalamin' => 1.0,
            'p-5-p' => 0.95, 'pyridoxal-5-phosphate' => 0.95,
            'chelated minerals' => 0.90, 'd3' => 0.90,
            'mixed tocopherols' => 0.85,
        ],
        'penalized_forms' => [
            'folic acid' => -1.5, 'cyanocobalamin' => -1.5,
            'dl-alpha tocopherol' => -1.0, 'zinc oxide' => -1.0,
            'magnesium oxide' => -1.5,
        ],
        'red_flags' => [
            'proprietary blend',
            'folic acid.*prenatal',
            'iron.*calcium.*same tablet',
        ],
        'key_ingredients' => ['multivitamin', 'multi vitamin'],
        'cofactors' => [],
        'notes' => 'Best multivitamins use methylated B vitamins and chelated minerals. Avoid those with folic acid and oxide mineral forms.',
    ],

    // =========================================================================
    // 22. CREATINE
    // =========================================================================
    'creatine' => [
        'therapeutic_range' => [
            'min' => 3, 'max' => 10, 'optimal' => 5,
            'unit' => 'g',
        ],
        'preferred_forms' => [
            'creatine monohydrate' => 1.0, 'monohydrate' => 1.0,
            'creapure' => 1.0,
            'micronized' => 0.95,
        ],
        'penalized_forms' => [
            'creatine ethyl ester' => -1.5,
            'creatine hydrochloride' => -0.5,
            'buffered creatine' => -0.5,
        ],
        'red_flags' => [
            'proprietary blend',
            'creatine.*liquid',
        ],
        'key_ingredients' => ['creatine', 'κρεατίνη'],
        'cofactors' => [],
        'notes' => 'Monohydrate is the most studied and effective form. CreaPure is the gold standard. 5g/day, no loading needed. Other forms are marketing gimmicks.',
    ],

    // =========================================================================
    // 23. GREENS & SUPERFOODS
    // =========================================================================
    'greens-superfoods' => [
        'therapeutic_range' => [
            'min' => 3, 'max' => 15, 'optimal' => 8,
            'unit' => 'g',
        ],
        'preferred_forms' => [
            'organic' => 0.90,
            'cold-pressed' => 0.90,
            'freeze-dried' => 0.85,
            'whole food' => 0.85,
            'fermented' => 0.80,
        ],
        'penalized_forms' => [
            'added sugar' => -2.0,
            'artificial sweetener' => -1.0,
            'artificial colors' => -1.5,
        ],
        'red_flags' => [
            'proprietary blend',
            'added sugar.*greens',
            'artificial sweetener',
        ],
        'key_ingredients' => ['spirulina', 'chlorella', 'barley grass', 'wheat grass', 'greens', 'superfood'],
        'cofactors' => ['probiotics', 'digestive enzymes', 'prebiotics'],
        'notes' => 'Look for products listing actual amounts per ingredient, not proprietary blends. Organic and third-party tested preferred.',
    ],

    // =========================================================================
    // 24. NMN / NAD+
    // =========================================================================
    'nmn-nad' => [
        'therapeutic_range' => [
            'min' => 250, 'max' => 1000, 'optimal' => 500,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'β-nmn' => 1.0, 'beta-nmn' => 1.0,
            'uthever' => 1.0,
            'sublingual' => 0.95,
            'liposomal' => 0.95,
            'nicotinamide riboside' => 0.85,
            'niagen' => 0.85,
        ],
        'penalized_forms' => [
            'nicotinamide' => -1.0,
            'niacin.*flush' => -0.5,
        ],
        'red_flags' => [
            'proprietary blend',
            'no purity testing',
        ],
        'key_ingredients' => ['nmn', 'nad+', 'nicotinamide mononucleotide', 'nicotinamide riboside'],
        'cofactors' => ['resveratrol', 'tmg', 'trimethylglycine'],
        'notes' => 'NMN and NR are NAD+ precursors. Uthever is a patented, purity-tested NMN. Sublingual delivery may improve bioavailability. Emerging research area.',
    ],

    // =========================================================================
    // 25. INOSITOL
    // =========================================================================
    'inositol' => [
        'therapeutic_range' => [
            'min' => 2000,
            'max' => 4000,
            'optimal' => 4000,
            'unit' => 'mg',
        ],
        'preferred_forms' => [
            'myo-inositol' => 1.0,
            'myo inositol' => 1.0,
            'd-chiro-inositol' => 0.95,
            'd-chiro inositol' => 0.95,
            '40:1 ratio' => 1.0,          // optimal myo:d-chiro ratio
            '40:1' => 1.0,
        ],
        'penalized_forms' => [
            'inositol hexaphosphate' => -1.0,  // IP6, different use
        ],
        'red_flags' => [
            'proprietary blend',
            'underdosed inositol',
            'inositol.*gummy',  // hard to fit 2000-4000mg in gummies
        ],
        'key_ingredients' => ['inositol', 'ινοσιτόλη', 'myo-inositol', 'd-chiro-inositol'],
        'cofactors' => ['folate', 'vitamin d', 'chromium', 'magnesium', 'omega-3'],
        'notes' => 'Myo-inositol is the most studied form, especially for PCOS (4000mg/day) and insulin resistance. The 40:1 ratio of myo:d-chiro mirrors the body\'s natural ratio. Often combined with folate for fertility. Also studied for anxiety and OCD at 12-18g/day. Must be powder form at therapeutic doses — capsules cannot deliver enough.',
    ],

];
