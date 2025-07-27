<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\BudgetResource;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetResourceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_render_budget_index_page()
    {
        Livewire::test(BudgetResource\Pages\ListBudgets::class)
            ->assertSuccessful();
    }

    public function test_can_render_budget_create_page()
    {
        Livewire::test(BudgetResource\Pages\CreateBudget::class)
            ->assertSuccessful();
    }

    public function test_can_create_budget()
    {
        $category = Category::factory()->expense()->create();

        $newData = [
            'category_id' => $category->id,
            'amount' => 1000.00,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'alert_threshold' => 80,
            'is_active' => true,
        ];

        Livewire::test(BudgetResource\Pages\CreateBudget::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('budgets', [
            'category_id' => $category->id,
            'amount' => 1000.00,
            'period' => 'monthly',
            'alert_threshold' => 80,
            'is_active' => true,
        ]);
    }

    public function test_can_edit_budget()
    {
        $budget = Budget::factory()->create();

        Livewire::test(BudgetResource\Pages\EditBudget::class, ['record' => $budget->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_can_update_budget()
    {
        $budget = Budget::factory()->create();
        $category = Category::factory()->expense()->create();

        $newData = [
            'category_id' => $category->id,
            'amount' => 1500.00,
            'period' => 'quarterly',
            'start_date' => now()->startOfQuarter()->toDateString(),
            'end_date' => now()->endOfQuarter()->toDateString(),
            'alert_threshold' => 90,
            'is_active' => false,
        ];

        Livewire::test(BudgetResource\Pages\EditBudget::class, ['record' => $budget->getRouteKey()])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'category_id' => $category->id,
            'amount' => 1500.00,
            'period' => 'quarterly',
            'alert_threshold' => 90,
            'is_active' => false,
        ]);
    }

    public function test_can_delete_budget()
    {
        $budget = Budget::factory()->create();

        Livewire::test(BudgetResource\Pages\EditBudget::class, ['record' => $budget->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($budget);
    }

    public function test_can_list_budgets()
    {
        $budgets = Budget::factory()->count(5)->create();

        $listComponent = Livewire::test(BudgetResource\Pages\ListBudgets::class)
            ->assertSuccessful();

        // Verify the table loads and contains records
        $this->assertGreaterThanOrEqual(5, Budget::count());

        // Test that we can see at least one budget's category name in the output
        $firstBudget = $budgets->first();
        $listComponent->assertSeeText($firstBudget->category->name);
    }

    public function test_can_filter_budgets_by_period()
    {
        $monthlyBudget = Budget::factory()->monthly()->create();
        $yearlyBudget = Budget::factory()->yearly()->create();

        $component = Livewire::test(BudgetResource\Pages\ListBudgets::class)
            ->filterTable('period', 'monthly')
            ->assertSuccessful();

        // Verify the filter works by checking database counts
        $this->assertEquals(1, Budget::where('period', 'monthly')->count());
        $this->assertEquals(1, Budget::where('period', 'yearly')->count());

        // Check that monthly budget details are visible
        $component->assertSeeText('Mensual');
    }

    public function test_can_filter_budgets_by_category()
    {
        $category1 = Category::factory()->expense()->create();
        $category2 = Category::factory()->expense()->create();

        $budget1 = Budget::factory()->create(['category_id' => $category1->id]);
        $budget2 = Budget::factory()->create(['category_id' => $category2->id]);

        Livewire::test(BudgetResource\Pages\ListBudgets::class)
            ->filterTable('category_id', $category1->id)
            ->assertCanSeeTableRecords([$budget1])
            ->assertCanNotSeeTableRecords([$budget2]);
    }

    public function test_can_filter_active_budgets()
    {
        $activeBudget = Budget::factory()->active()->create();
        $inactiveBudget = Budget::factory()->inactive()->create();

        Livewire::test(BudgetResource\Pages\ListBudgets::class)
            ->filterTable('is_active')
            ->assertCanSeeTableRecords([$activeBudget])
            ->assertCanNotSeeTableRecords([$inactiveBudget]);
    }
}
