<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplement extends Model
{
    protected $fillable = [
        'pid', 'sku', 'brand', 'title', 'product_url', 'image_url',
        'current_price', 'currency', 'rating', 'review_count',
        'category_id', 'comparison_group', 'product_description',
        'nutritional_facts', 'serving_size_value', 'serving_size_unit',
        'servings_per_container', 'cost_per_serving', 'cost_per_day',
        'dosage_form', 'certification_flags', 'suggested_use',
        'other_ingredients', 'warnings', 'allergen_contains_flags',
        'allergen_free_from_flags', 'active_ingredients',
        'daily_dose_recommended', 'daily_servings_estimate',
        'days_of_supply', 'warning_flags', 'extraction_confidence',
        'efficacy_score', 'bioavailability_score', 'quality_score',
        'value_score', 'formulation_score', 'overall_recommendation_score'
    ];

    protected $casts = [
        'certification_flags' => 'array',
        'allergen_contains_flags' => 'array',
        'allergen_free_from_flags' => 'array',
        'active_ingredients' => 'array',
        'warning_flags' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(SupplementCategory::class);
    }

    public function conditions()
    {
        return $this->belongsToMany(MedicalCondition::class, 'supplement_condition_mappings')
            ->withPivot('efficacy_rating', 'evidence_level', 'contraindication', 'notes')
            ->withTimestamps();
    }
}
