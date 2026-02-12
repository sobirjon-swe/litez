<?php

namespace Tests\Unit\Inventory;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\DTOs\StockMovementReason;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();
        $category = Category::factory()->create();
        $this->product = Product::factory()->create(['category_id' => $category->id]);
        Stock::create([
            'product_id' => $this->product->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
        ]);
    }

    public function test_receipt_increases_quantity(): void
    {
        $movement = $this->service->adjust($this->product, 30, StockMovementReason::Receipt);

        $this->assertEquals(30, $movement->quantity_change);
        $this->assertEquals(80, $this->product->stock->fresh()->quantity);
    }

    public function test_sale_decreases_quantity_and_increases_reserved(): void
    {
        $this->service->adjust($this->product, -10, StockMovementReason::Sale);

        $stock = $this->product->stock->fresh();
        $this->assertEquals(40, $stock->quantity);
        $this->assertEquals(10, $stock->reserved_quantity);
    }

    public function test_adjustment_does_not_change_reserved(): void
    {
        $this->service->adjust($this->product, -5, StockMovementReason::Adjustment);

        $stock = $this->product->stock->fresh();
        $this->assertEquals(45, $stock->quantity);
        $this->assertEquals(0, $stock->reserved_quantity);
    }

    public function test_throws_exception_when_quantity_goes_negative(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->adjust($this->product, -100, StockMovementReason::Sale);
    }

    public function test_creates_stock_if_not_exists(): void
    {
        $newProduct = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
        ]);

        $movement = $this->service->adjust($newProduct, 10, StockMovementReason::Receipt);

        $this->assertEquals(10, $movement->quantity_change);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $newProduct->id,
            'quantity' => 10,
        ]);
    }
}
