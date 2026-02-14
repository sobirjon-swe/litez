<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create();
    }

    public function test_can_create_product_with_attributes(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Futbolka Classic',
            'price' => 29900,
            'sku' => 'TSH-001',
            'category_id' => $this->category->id,
            'is_published' => true,
            'attributes' => [
                ['name' => 'rang', 'value' => 'qora'],
                ['name' => 'o\'lcham', 'value' => 'XL'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Futbolka Classic')
            ->assertJsonPath('data.slug', 'futbolka-classic')
            ->assertJsonPath('data.sku', 'TSH-001')
            ->assertJsonCount(2, 'data.attributes');
    }

    public function test_product_slug_auto_increments_on_duplicate(): void
    {
        Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson('/api/products', [
            'name' => 'Test Product',
            'price' => 1000,
            'sku' => 'TST-002',
            'category_id' => $this->category->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'test-product-1');
    }

    public function test_can_get_product_by_slug(): void
    {
        $product = Product::factory()->create([
            'slug' => 'my-product',
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/products/my-product');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_can_filter_products_by_category(): void
    {
        Product::factory()->create(['category_id' => $this->category->id]);
        $otherCategory = Category::factory()->create();
        Product::factory()->create(['category_id' => $otherCategory->id]);

        $response = $this->getJson('/api/products?category_id=' . $this->category->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_products_by_price_range(): void
    {
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 5000]);
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 15000]);
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 50000]);

        $response = $this->getJson('/api/products?price_min=4000&price_max=20000');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_products(): void
    {
        Product::factory()->create([
            'name' => 'Samsung Galaxy',
            'category_id' => $this->category->id,
        ]);
        Product::factory()->create([
            'name' => 'iPhone 15',
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/products?search=Samsung');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->putJson('/api/products/' . $product->id, [
            'name' => 'Updated Name',
            'price' => 99900,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.price', '99900.00');
    }

    public function test_create_product_requires_fields(): void
    {
        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'sku', 'category_id']);
    }

    public function test_sku_must_be_unique(): void
    {
        Product::factory()->create([
            'sku' => 'UNIQUE-001',
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson('/api/products', [
            'name' => 'Another Product',
            'price' => 1000,
            'sku' => 'UNIQUE-001',
            'category_id' => $this->category->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('sku');
    }

    public function test_product_created_with_stock(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'New Product',
            'price' => 5000,
            'sku' => 'NEW-001',
            'category_id' => $this->category->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.stock.quantity', 0)
            ->assertJsonPath('data.stock.reserved_quantity', 0);
    }
}
