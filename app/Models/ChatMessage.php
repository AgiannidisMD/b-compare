<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'extracted_data',
    ];

    protected $casts = [
        'extracted_data' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class);
    }
}
