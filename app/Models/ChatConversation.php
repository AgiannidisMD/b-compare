<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $fillable = [
        'session_id',
        'user_ip',
        'selected_category_id',
        'extracted_conditions',
        'extracted_preferences',
        'confidence_score',
        'is_ready_to_recommend',
        'conversation_history',
    ];

    protected $casts = [
        'extracted_conditions' => 'array',
        'extracted_preferences' => 'array',
        'conversation_history' => 'array',
        'is_ready_to_recommend' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(SupplementCategory::class, 'selected_category_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function recommendationEvents()
    {
        return $this->hasMany(RecommendationEvent::class, 'conversation_id');
    }
}
