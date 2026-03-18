<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyStat extends Model
{
    protected $fillable = [
        'date',
        'conversations_count',
        'messages_count',
        'recommendations_count',
        'unique_sessions',
        'top_categories',
        'top_conditions',
        'top_supplements',
        'hourly_distribution',
    ];

    protected $casts = [
        'date' => 'date',
        'top_categories' => 'array',
        'top_conditions' => 'array',
        'top_supplements' => 'array',
        'hourly_distribution' => 'array',
    ];
}
