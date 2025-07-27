<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_creation()
    {
        $account = Account::create([
            'name' => 'Test Account',
            'type' => 'checking',
            'balance' => 1000.00,
            'currency' => 'COP',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('accounts', [
            'name' => 'Test Account',
            'type' => 'checking',
            'balance' => 1000.00,
            'currency' => 'COP',
            'is_active' => true,
        ]);
    }

    public function test_account_has_transactions_relationship()
    {
        $account = Account::factory()->create();
        $transaction = Transaction::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($account->transactions->contains($transaction));
    }

    public function test_formatted_balance_attribute()
    {
        $account = Account::factory()->create(['balance' => 1234567.89, 'currency' => 'COP']);

        $this->assertEquals('$ 1,234,567.89 COP', $account->formatted_balance);
    }

    public function test_masked_account_number_attribute()
    {
        $account = Account::factory()->create(['account_number' => '1234567890']);

        $this->assertEquals('******7890', $account->masked_account_number);
    }

    public function test_update_balance_from_transaction()
    {
        $account = Account::factory()->create(['balance' => 1000.00]);

        // Create income transaction
        Transaction::factory()->create([
            'account_id' => $account->id,
            'amount' => 500.00,
            'type' => 'income',
        ]);

        $account->refresh();
        $this->assertEquals(1500.00, $account->balance);
    }

    public function test_account_type_validation()
    {
        $validTypes = ['checking', 'savings', 'credit_card', 'cash', 'investment'];

        foreach ($validTypes as $type) {
            $account = Account::factory()->create(['type' => $type]);
            $this->assertEquals($type, $account->type);
        }
    }
}
