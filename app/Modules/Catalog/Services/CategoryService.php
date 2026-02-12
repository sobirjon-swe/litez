<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
    public function getTree(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::whereNull('parent_id')
            ->with('children.children')
            ->get();
    }

    public function create(array $data): Category
    {
        $data['slug'] = $this->generateUniqueSlug($data['slug'] ?? null, $data['name']);

        return Category::create($data);
    }

    private function generateUniqueSlug(?string $slug, string $name): string
    {
        $slug = $slug ?: Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (Category::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }
}
