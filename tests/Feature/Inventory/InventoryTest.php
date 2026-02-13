<?php

namespace Tests\Feature\Inventory;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $category = Category::factory()->create();
        $this->product = Product::factory()->create(['category_id' => $category->id]);
        $this->stock = Stock::create([
            'product_id' => $this->product->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
        ]);
    }

    public function test_can_adjust_stock_receipt(): void
    {
        $response = $this->postJson("/api/inventory/{$this->product->id}/adjust", [
            'quantity_change' => 20,
            'reason' => 'receipt',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.quantity_change', 20)
            ->assertJsonPath('data.reason', 'receipt');

        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'quantity' => 70,
        ]);
    }

    public function test_can_adjust_stock_sale(): void
    {
        $response = $this->postJson("/api/inventory/{$this->product->id}/adjust", [
            'quantity_change' => -10,
            'reason' => 'sale',
        ]);

        $response->assertStatus(201);

        $this->stock->refresh();
        $this->assertEquals(40, $this->stock->quantity);
        $this->assertEquals(10, $this->stock->reserved_quantity);
    }

    public function test_cannot_adjust_below_zero(): void
    {
        $response = $this->postJson("/api/inventory/{$this->product->id}/adjust", [
            'quantity_change' => -100,
            'reason' => 'sale',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('quantity_change');
    }

    public function test_reserved_quantity_not_affected_by_non_sale_reasons(): void
    {
        $this->postJson("/api/inventory/{$this->product->id}/adjust", [
            'quantity_change' => -5,
            'reason' => 'adjustment',
        ]);

        $this->stock->refresh();
        $this->assertEquals(45, $this->stock->quantity);
        $this->assertEquals(0, $this->stock->reserved_quantity);
    }

    public function test_can_get_stock_movement_history(): void
    {
        $this->postJson("/api/inventory/{$this->product->id}/adjust", [
            'quantity_change' => 10,
            'reason' => 'receipt',
        ]);

        $this->postJson("/api/inventory/{$this->product->id}/adjust", [
            'quantity_change' => -5,
            'reason' => 'sale',
        ]);

        $response = $this->getJson("/api/inventory/{$this->product->id}/history");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_adjust_requires_valid_reason(): void
    {
        $response = $this->postJson("/api/inventory/{$this->product->id}/adjust", [
            'quantity_change' => 10,
            'reason' => 'invalid_reason',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_adjust_requires_non_zero_quantity(): void
    {
        $response = $this->postJson("/api/inventory/{$this->product->id}/adjust", [
            'quantity_change' => 0,
            'reason' => 'receipt',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('quantity_change');
    }
}
