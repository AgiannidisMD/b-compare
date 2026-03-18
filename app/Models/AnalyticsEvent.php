<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    protected $fillable = [
        'event_type',
        'supplement_id',
        'category_id',
        'condition_id',
        'conversation_id',
        'session_id',
        'user_ip',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function supplement()
    {
        return $this->belongsTo(Supplement::class);
    }

    public function category()
    {
        return $this->belongsTo(SupplementCategory::class);
    }

    public function condition()
    {
        return $this->belongsTo(MedicalCondition::class);
    }
}
