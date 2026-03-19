<?php

namespace App\Http\Controllers;

use App\Models\HealthFunction;
use App\Models\SupplementCategory;
use Illuminate\Http\Request;

class BrowseController extends Controller
{
    public function index()
    {
        $functions = HealthFunction::orderBy('sort_order')->get()->map(function ($f) {
            return [
                'slug' => $f->slug,
                'name' => $f->name,
                'name_el' => $f->name_el,
                'icon' => $f->icon,
                'category_count' => $f->categories()->count(),
            ];
        });

        $categories = SupplementCategory::orderByDesc('product_count')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'slug' => $c->slug,
                    'name' => $c->name,
                    'icon' => $c->icon,
                    'product_count' => $c->product_count,
                ];
            });

        return view('browse', compact('functions', 'categories'));
    }
}
