<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Product::with(['category', 'attributes', 'stock']);

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function findBySlug(string $slug): Product
    {
        return Product::with(['category', 'attributes', 'stock'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data['attributes'] ?? [];
            unset($data['attributes']);

            $data['slug'] = $this->generateUniqueSlug($data['slug'] ?? null, $data['name']);

            $product = Product::create($data);

            foreach ($attributes as $attribute) {
                $product->attributes()->create($attribute);
            }

            $product->stock()->create([
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]);

            return $product->load(['category', 'attributes', 'stock']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $attributes = $data['attributes'] ?? null;
            unset($data['attributes']);

            if (isset($data['name']) && (!isset($data['slug']) || !$data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug(null, $data['name'], $product->id);
            }

            $product->update($data);

            if ($attributes !== null) {
                $product->attributes()->delete();
                foreach ($attributes as $attribute) {
                    $product->attributes()->create($attribute);
                }
            }

            return $product->load(['category', 'attributes', 'stock']);
        });
    }

    private function generateUniqueSlug(?string $slug, string $name, ?int $excludeId = null): string
    {
        $slug = $slug ?: Str::slug($name);
        $original = $slug;
        $counter = 1;

        $query = Product::withTrashed()->where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $original . '-' . $counter++;
            $query = Product::withTrashed()->where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }
}
