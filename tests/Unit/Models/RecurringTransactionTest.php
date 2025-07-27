<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_transaction_creation()
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create();

        $recurringTransaction = RecurringTransaction::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 500.00,
            'description' => 'Monthly salary',
            'frequency' => 'monthly',
            'next_due_date' => now()->addMonth(),
            'is_active' => true,
            'auto_process' => false,
        ]);

        $this->assertDatabaseHas('recurring_transactions', [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 500.00,
            'description' => 'Monthly salary',
            'frequency' => 'monthly',
            'is_active' => true,
            'auto_process' => false,
        ]);
    }

    public function test_recurring_transaction_belongs_to_account()
    {
        $account = Account::factory()->create();
        $recurringTransaction = RecurringTransaction::factory()->create(['account_id' => $account->id]);

        $this->assertEquals($account->id, $recurringTransaction->account->id);
    }

    public function test_recurring_transaction_belongs_to_category()
    {
        $category = Category::factory()->create();
        $recurringTransaction = RecurringTransaction::factory()->create(['category_id' => $category->id]);

        $this->assertEquals($category->id, $recurringTransaction->category->id);
    }

    public function test_signed_amount_attribute()
    {
        $positiveTransaction = RecurringTransaction::factory()->create(['amount' => 100.00]);
        $negativeTransaction = RecurringTransaction::factory()->create(['amount' => -100.00]);

        $this->assertEquals('+$ 100.00', $positiveTransaction->signed_amount);
        $this->assertEquals('-$ 100.00', $negativeTransaction->signed_amount);
    }

    public function test_frequency_label_attribute()
    {
        $frequencies = [
            'daily' => 'Diario',
            'weekly' => 'Semanal',
            'monthly' => 'Mensual',
            'quarterly' => 'Trimestral',
            'yearly' => 'Anual',
        ];

        foreach ($frequencies as $frequency => $label) {
            $recurringTransaction = RecurringTransaction::factory()->create(['frequency' => $frequency]);
            $this->assertEquals($label, $recurringTransaction->frequency_label);
        }
    }

    public function test_days_until_due_attribute()
    {
        $recurringTransaction = RecurringTransaction::factory()->create([
            'next_due_date' => now()->addDays(5),
        ]);

        $this->assertEquals(5, $recurringTransaction->days_until_due);
    }

    public function test_is_due_attribute()
    {
        $dueTransaction = RecurringTransaction::factory()->create([
            'next_due_date' => now(),
        ]);

        $notDueTransaction = RecurringTransaction::factory()->create([
            'next_due_date' => now()->addDays(5),
        ]);

        $this->assertTrue($dueTransaction->is_due);
        $this->assertFalse($notDueTransaction->is_due);
    }

    public function test_is_overdue_attribute()
    {
        $overdueTransaction = RecurringTransaction::factory()->create([
            'next_due_date' => now()->subDay(),
        ]);

        $notOverdueTransaction = RecurringTransaction::factory()->create([
            'next_due_date' => now()->addDay(),
        ]);

        $this->assertTrue($overdueTransaction->is_overdue);
        $this->assertFalse($notOverdueTransaction->is_overdue);
    }

    public function test_can_process_method()
    {
        $activeTransaction = RecurringTransaction::factory()->create([
            'is_active' => true,
            'next_due_date' => now(),
        ]);

        $inactiveTransaction = RecurringTransaction::factory()->create([
            'is_active' => false,
            'next_due_date' => now(),
        ]);

        $futureTransaction = RecurringTransaction::factory()->create([
            'is_active' => true,
            'next_due_date' => now()->addDays(5),
        ]);

        $this->assertTrue($activeTransaction->canProcess());
        $this->assertFalse($inactiveTransaction->canProcess());
        $this->assertFalse($futureTransaction->canProcess());
    }

    public function test_due_scope()
    {
        $dueTransaction = RecurringTransaction::factory()->create([
            'next_due_date' => now(),
        ]);

        $futureTransaction = RecurringTransaction::factory()->create([
            'next_due_date' => now()->addDays(5),
        ]);

        $dueTransactions = RecurringTransaction::due()->get();

        $this->assertTrue($dueTransactions->contains($dueTransaction));
        $this->assertFalse($dueTransactions->contains($futureTransaction));
    }

    public function test_overdue_scope()
    {
        $overdueTransaction = RecurringTransaction::factory()->create([
            'next_due_date' => now()->subDay(),
        ]);

        $currentTransaction = RecurringTransaction::factory()->create([
            'next_due_date' => now(),
        ]);

        $overdueTransactions = RecurringTransaction::overdue()->get();

        $this->assertTrue($overdueTransactions->contains($overdueTransaction));
        $this->assertFalse($overdueTransactions->contains($currentTransaction));
    }

    public function test_not_expired_scope()
    {
        $activeTransaction = RecurringTransaction::factory()->create([
            'end_date' => null,
        ]);

        $futureTransaction = RecurringTransaction::factory()->create([
            'next_due_date' => now()->addWeek(),
            'end_date' => now()->addMonth(),
        ]);

        $expiredTransaction = RecurringTransaction::factory()->create([
            'next_due_date' => now()->subWeek(),
            'end_date' => now()->subDay(),
        ]);

        $notExpiredTransactions = RecurringTransaction::notExpired()->get();

        $this->assertTrue($notExpiredTransactions->contains($activeTransaction));
        $this->assertTrue($notExpiredTransactions->contains($futureTransaction));
        $this->assertFalse($notExpiredTransactions->contains($expiredTransaction));
    }
}
