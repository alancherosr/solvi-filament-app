<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_creation()
    {
        $category = Category::create([
            'name' => 'Test Category',
            'type' => 'expense',
            'color' => '#FF0000',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'type' => 'expense',
            'color' => '#FF0000',
            'is_active' => true,
        ]);
    }

    public function test_category_hierarchy_relationship()
    {
        $parent = Category::factory()->create(['name' => 'Parent Category']);
        $child = Category::factory()->create([
            'name' => 'Child Category',
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue($parent->children->contains($child));
        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function test_category_has_transactions_relationship()
    {
        $category = Category::factory()->create();
        $transaction = Transaction::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($category->transactions->contains($transaction));
    }

    public function test_full_name_attribute()
    {
        $parent = Category::factory()->create(['name' => 'Parent']);
        $child = Category::factory()->create([
            'name' => 'Child',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals('Parent > Child', $child->full_name);
        $this->assertEquals('Parent', $parent->full_name);
    }

    public function test_get_depth_method()
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $grandchild = Category::factory()->create(['parent_id' => $child->id]);

        $this->assertEquals(0, $parent->getDepth());
        $this->assertEquals(1, $child->getDepth());
        $this->assertEquals(2, $grandchild->getDepth());
    }

    public function test_category_type_validation()
    {
        $validTypes = ['income', 'expense'];

        foreach ($validTypes as $type) {
            $category = Category::factory()->create(['type' => $type]);
            $this->assertEquals($type, $category->type);
        }
    }
}
