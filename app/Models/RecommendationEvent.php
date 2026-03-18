<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecommendationEvent extends Model
{
    protected $fillable = [
        'conversation_id',
        'category_id',
        'condition_ids',
        'supplement_ids',
        'supplements_count',
        'session_id',
        'user_ip',
    ];

    protected $casts = [
        'condition_ids' => 'array',
        'supplement_ids' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class);
    }

    public function category()
    {
        return $this->belongsTo(SupplementCategory::class);
    }
}
