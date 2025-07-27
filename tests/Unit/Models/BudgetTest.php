<?php

namespace Tests\Unit\Models;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_creation()
    {
        $category = Category::factory()->create();

        $budget = Budget::create([
            'category_id' => $category->id,
            'amount' => 1000.00,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'alert_threshold' => 80,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('budgets', [
            'category_id' => $category->id,
            'amount' => 1000.00,
            'period' => 'monthly',
            'alert_threshold' => 80,
            'is_active' => true,
        ]);
    }

    public function test_budget_belongs_to_category()
    {
        $category = Category::factory()->create();
        $budget = Budget::factory()->create(['category_id' => $category->id]);

        $this->assertEquals($category->id, $budget->category->id);
    }

    public function test_formatted_amount_attribute()
    {
        $budget = Budget::factory()->create(['amount' => 1234.56]);

        $this->assertEquals('$ 1,234.56', $budget->formatted_amount);
    }

    public function test_spent_amount_calculation()
    {
        $category = Category::factory()->create(['type' => 'expense']);
        $budget = Budget::factory()->create([
            'category_id' => $category->id,
            'amount' => 1000.00,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        // Create transactions within budget period
        Transaction::factory()->create([
            'category_id' => $category->id,
            'amount' => 200.00,
            'type' => 'expense',
            'transaction_date' => now(),
        ]);

        Transaction::factory()->create([
            'category_id' => $category->id,
            'amount' => 300.00,
            'type' => 'expense',
            'transaction_date' => now(),
        ]);

        $this->assertEquals(500.00, $budget->spent_amount);
    }

    public function test_formatted_spent_amount_attribute()
    {
        $category = Category::factory()->create(['type' => 'expense']);
        $budget = Budget::factory()->create([
            'category_id' => $category->id,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        Transaction::factory()->create([
            'category_id' => $category->id,
            'amount' => 1234.56,
            'type' => 'expense',
            'transaction_date' => now(),
        ]);

        $this->assertEquals('$ 1,234.56', $budget->formatted_spent_amount);
    }

    public function test_percentage_used_attribute()
    {
        $category = Category::factory()->create(['type' => 'expense']);
        $budget = Budget::factory()->create([
            'category_id' => $category->id,
            'amount' => 1000.00,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        Transaction::factory()->create([
            'category_id' => $category->id,
            'amount' => 250.00,
            'type' => 'expense',
            'transaction_date' => now(),
        ]);

        $this->assertEquals(25.0, $budget->percentage_used);
    }

    public function test_is_over_budget_attribute()
    {
        $category = Category::factory()->create(['type' => 'expense']);
        $budget = Budget::factory()->create([
            'category_id' => $category->id,
            'amount' => 1000.00,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        // Spend more than budget
        Transaction::factory()->create([
            'category_id' => $category->id,
            'amount' => 1200.00,
            'type' => 'expense',
            'transaction_date' => now(),
        ]);

        $this->assertTrue($budget->is_over_budget);
    }

    public function test_status_attribute()
    {
        $category = Category::factory()->create(['type' => 'expense']);

        // Test on_track status
        $budget1 = Budget::factory()->create([
            'category_id' => $category->id,
            'amount' => 1000.00,
            'alert_threshold' => 80,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        Transaction::factory()->create([
            'category_id' => $category->id,
            'amount' => 500.00,
            'type' => 'expense',
            'transaction_date' => now(),
        ]);

        $this->assertEquals('on_track', $budget1->status);

        // Test warning status
        $category2 = Category::factory()->create(['type' => 'expense']);
        $budget2 = Budget::factory()->create([
            'category_id' => $category2->id,
            'amount' => 1000.00,
            'alert_threshold' => 80,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        Transaction::factory()->create([
            'category_id' => $category2->id,
            'amount' => 850.00,
            'type' => 'expense',
            'transaction_date' => now(),
        ]);

        $this->assertEquals('warning', $budget2->status);
    }

    public function test_current_period_scope()
    {
        $currentBudget = Budget::factory()->create([
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $pastBudget = Budget::factory()->create([
            'start_date' => now()->subMonth()->startOfMonth(),
            'end_date' => now()->subMonth()->endOfMonth(),
        ]);

        $currentBudgets = Budget::currentPeriod()->get();

        $this->assertTrue($currentBudgets->contains($currentBudget));
        $this->assertFalse($currentBudgets->contains($pastBudget));
    }
}
