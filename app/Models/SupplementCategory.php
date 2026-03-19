<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplementCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'product_count',
    ];

    public function supplements(): HasMany
    {
        return $this->hasMany(Supplement::class, 'category_id');
    }

    public function functions(): BelongsToMany
    {
        return $this->belongsToMany(HealthFunction::class, 'category_function')
            ->withPivot('relevance_score');
    }
}
