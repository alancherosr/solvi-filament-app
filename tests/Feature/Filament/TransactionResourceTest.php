<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\TransactionResource;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionResourceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        // Use Filament's actingAs for proper panel authentication
        $this->actingAs($this->user, 'web');
    }

    public function test_can_render_transaction_index_page()
    {
        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->assertSuccessful();
    }

    public function test_can_render_transaction_create_page()
    {
        Livewire::test(TransactionResource\Pages\CreateTransaction::class)
            ->assertSuccessful();
    }

    public function test_can_create_transaction()
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create(['type' => 'expense']);

        $newData = [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 100.00,
            'description' => 'Test transaction',
            'transaction_date' => now()->toDateString(),
            'type' => 'expense',
        ];

        Livewire::test(TransactionResource\Pages\CreateTransaction::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 100.00,
            'description' => 'Test transaction',
            'type' => 'expense',
        ]);
    }

    public function test_can_edit_transaction()
    {
        $transaction = Transaction::factory()->create();

        Livewire::test(TransactionResource\Pages\EditTransaction::class, ['record' => $transaction->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_can_update_transaction()
    {
        $transaction = Transaction::factory()->create();
        $account = Account::factory()->create();
        $category = Category::factory()->create();

        $newData = [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 200.00,
            'description' => 'Updated transaction',
            'transaction_date' => now()->toDateString(),
            'type' => 'income',
        ];

        Livewire::test(TransactionResource\Pages\EditTransaction::class, ['record' => $transaction->getRouteKey()])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 200.00,
            'description' => 'Updated transaction',
            'type' => 'income',
        ]);
    }

    public function test_can_delete_transaction()
    {
        $transaction = Transaction::factory()->create();

        Livewire::test(TransactionResource\Pages\EditTransaction::class, ['record' => $transaction->getRouteKey()])
            ->callAction('delete');

        $this->assertSoftDeleted($transaction);
    }

    public function test_can_list_transactions()
    {
        $transactions = Transaction::factory()->count(10)->create();

        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->assertCanSeeTableRecords($transactions);
    }

    public function test_can_filter_transactions_by_type()
    {
        $incomeTransaction = Transaction::factory()->income()->create();
        $expenseTransaction = Transaction::factory()->expense()->create();

        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->filterTable('type', 'income')
            ->assertCanSeeTableRecords([$incomeTransaction])
            ->assertCanNotSeeTableRecords([$expenseTransaction]);
    }

    public function test_can_filter_transactions_by_account()
    {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $transaction1 = Transaction::factory()->create(['account_id' => $account1->id]);
        $transaction2 = Transaction::factory()->create(['account_id' => $account2->id]);

        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->filterTable('account_id', $account1->id)
            ->assertCanSeeTableRecords([$transaction1])
            ->assertCanNotSeeTableRecords([$transaction2]);
    }

    public function test_can_search_transactions()
    {
        $transaction1 = Transaction::factory()->create(['description' => 'Coffee shop purchase']);
        $transaction2 = Transaction::factory()->create(['description' => 'Grocery store']);

        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->searchTable('coffee')
            ->assertCanSeeTableRecords([$transaction1])
            ->assertCanNotSeeTableRecords([$transaction2]);
    }
}
