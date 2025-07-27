<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_creation()
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create();

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 100.00,
            'description' => 'Test transaction',
            'transaction_date' => now(),
            'type' => 'expense',
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 100.00,
            'description' => 'Test transaction',
            'type' => 'expense',
        ]);
    }

    public function test_transaction_belongs_to_account()
    {
        $account = Account::factory()->create();
        $transaction = Transaction::factory()->create(['account_id' => $account->id]);

        $this->assertEquals($account->id, $transaction->account->id);
    }

    public function test_transaction_belongs_to_category()
    {
        $category = Category::factory()->create();
        $transaction = Transaction::factory()->create(['category_id' => $category->id]);

        $this->assertEquals($category->id, $transaction->category->id);
    }

    public function test_signed_amount_attribute()
    {
        $incomeTransaction = Transaction::factory()->create([
            'amount' => 100.00,
            'type' => 'income',
        ]);

        $expenseTransaction = Transaction::factory()->create([
            'amount' => 100.00,
            'type' => 'expense',
        ]);

        $this->assertEquals('+$ 100.00', $incomeTransaction->signed_amount);
        $this->assertEquals('-$ 100.00', $expenseTransaction->signed_amount);
    }

    public function test_transfer_transaction_relationship()
    {
        $fromAccount = Account::factory()->create();
        $toAccount = Account::factory()->create();

        $transfer = Transaction::factory()->create([
            'account_id' => $fromAccount->id,
            'transfer_to_account_id' => $toAccount->id,
            'type' => 'transfer',
        ]);

        $this->assertEquals($toAccount->id, $transfer->transferToAccount->id);
    }

    public function test_transaction_updates_account_balance()
    {
        $account = Account::factory()->create(['balance' => 1000.00]);
        $initialBalance = $account->balance;

        // Create expense transaction
        Transaction::factory()->create([
            'account_id' => $account->id,
            'amount' => 200.00,
            'type' => 'expense',
        ]);

        $account->refresh();
        $this->assertEquals($initialBalance - 200.00, $account->balance);
    }

    public function test_this_month_scope()
    {
        $thisMonthTransaction = Transaction::factory()->create([
            'transaction_date' => now(),
        ]);

        $lastMonthTransaction = Transaction::factory()->create([
            'transaction_date' => now()->subMonth(),
        ]);

        $thisMonthTransactions = Transaction::thisMonth()->get();

        $this->assertTrue($thisMonthTransactions->contains($thisMonthTransaction));
        $this->assertFalse($thisMonthTransactions->contains($lastMonthTransaction));
    }

    public function test_this_year_scope()
    {
        $thisYearTransaction = Transaction::factory()->create([
            'transaction_date' => now(),
        ]);

        $lastYearTransaction = Transaction::factory()->create([
            'transaction_date' => now()->subYear(),
        ]);

        $thisYearTransactions = Transaction::thisYear()->get();

        $this->assertTrue($thisYearTransactions->contains($thisYearTransaction));
        $this->assertFalse($thisYearTransactions->contains($lastYearTransaction));
    }
}
