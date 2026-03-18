<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplementConditionMapping extends Model
{
    protected $fillable = [
        'supplement_id',
        'condition_id',
        'efficacy_rating',
        'evidence_level',
        'contraindication',
        'notes',
    ];

    protected $casts = [
        'efficacy_rating' => 'decimal:2',
        'contraindication' => 'boolean',
    ];

    public function supplement(): BelongsTo
    {
        return $this->belongsTo(Supplement::class);
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(MedicalCondition::class, 'condition_id');
    }
}
