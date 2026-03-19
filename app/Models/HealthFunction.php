<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HealthFunction extends Model
{
    protected $fillable = [
        'name', 'name_el', 'slug', 'description', 'description_el', 'icon', 'sort_order',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(SupplementCategory::class, 'category_function')
            ->withPivot('relevance_score')
            ->orderByPivot('relevance_score', 'desc');
    }
}
