<?php

namespace Tests\Unit\Catalog;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugGenerationTest extends TestCase
{
    use RefreshDatabase;

    private CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CategoryService();
    }

    public function test_slug_generated_from_name(): void
    {
        $category = $this->service->create(['name' => 'Elektronika']);

        $this->assertEquals('elektronika', $category->slug);
    }

    public function test_slug_increments_when_duplicate(): void
    {
        $this->service->create(['name' => 'Elektronika']);
        $category2 = $this->service->create(['name' => 'Elektronika']);

        $this->assertEquals('elektronika-1', $category2->slug);
    }

    public function test_custom_slug_is_used(): void
    {
        $category = $this->service->create([
            'name' => 'Elektronika',
            'slug' => 'custom-slug',
        ]);

        $this->assertEquals('custom-slug', $category->slug);
    }
}
