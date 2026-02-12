<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Inventory\Models\Stock;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $electronics = Category::create(['name' => 'Elektronika', 'slug' => 'elektronika', 'is_active' => true]);
        $phones = Category::create(['name' => 'Telefonlar', 'slug' => 'telefonlar', 'parent_id' => $electronics->id, 'is_active' => true]);
        $accessories = Category::create(['name' => 'Aksessuarlar', 'slug' => 'aksessuarlar', 'parent_id' => $phones->id, 'is_active' => true]);

        $clothing = Category::create(['name' => 'Kiyimlar', 'slug' => 'kiyimlar', 'is_active' => true]);
        $men = Category::create(['name' => 'Erkaklar uchun', 'slug' => 'erkaklar-uchun', 'parent_id' => $clothing->id, 'is_active' => true]);

        $categories = [$phones, $accessories, $men, $electronics, $clothing];

        foreach ($categories as $category) {
            $products = Product::factory(3)->create([
                'category_id' => $category->id,
            ]);

            foreach ($products as $product) {
                ProductAttribute::create(['product_id' => $product->id, 'name' => 'rang', 'value' => fake()->randomElement(['qora', 'oq', 'ko\'k', 'qizil'])]);
                ProductAttribute::create(['product_id' => $product->id, 'name' => 'o\'lcham', 'value' => fake()->randomElement(['S', 'M', 'L', 'XL'])]);

                Stock::create([
                    'product_id' => $product->id,
                    'quantity' => fake()->numberBetween(5, 100),
                    'reserved_quantity' => 0,
                ]);
            }
        }
    }
}
