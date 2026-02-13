<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_category(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Elektronika',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Elektronika')
            ->assertJsonPath('data.slug', 'elektronika');
    }

    public function test_slug_auto_generated_from_name(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Uy jihozlari',
        ]);

        $response->assertJsonPath('data.slug', 'uy-jihozlari');
    }

    public function test_duplicate_slug_gets_incremented(): void
    {
        Category::factory()->create(['name' => 'Test', 'slug' => 'test']);

        $response = $this->postJson('/api/categories', [
            'name' => 'Test',
        ]);

        $response->assertJsonPath('data.slug', 'test-1');
    }

    public function test_category_tree_max_depth_3(): void
    {
        $level1 = Category::factory()->create();
        $level2 = Category::factory()->create(['parent_id' => $level1->id]);
        $level3 = Category::factory()->create(['parent_id' => $level2->id]);

        $response = $this->postJson('/api/categories', [
            'name' => 'Level 4',
            'parent_id' => $level3->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_index_returns_tree_structure(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'slug', 'children'],
                ],
            ]);
    }

    public function test_create_category_requires_name(): void
    {
        $response = $this->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }
}
