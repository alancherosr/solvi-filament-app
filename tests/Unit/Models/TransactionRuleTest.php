<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_rule_creation()
    {
        $category = Category::factory()->create();

        $rule = TransactionRule::create([
            'name' => 'Test Rule',
            'category_id' => $category->id,
            'conditions' => [
                [
                    'field' => 'description',
                    'operator' => 'contains',
                    'value' => 'Uber',
                ],
            ],
            'priority' => 5,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('transaction_rules', [
            'name' => 'Test Rule',
            'category_id' => $category->id,
            'priority' => 5,
            'is_active' => true,
        ]);
    }

    public function test_transaction_rule_belongs_to_category()
    {
        $category = Category::factory()->create();
        $rule = TransactionRule::factory()->create(['category_id' => $category->id]);

        $this->assertEquals($category->id, $rule->category->id);
    }

    public function test_conditions_text_attribute()
    {
        $rule = TransactionRule::factory()->create([
            'conditions' => [
                [
                    'field' => 'description',
                    'operator' => 'contains',
                    'value' => 'Uber',
                ],
                [
                    'field' => 'amount',
                    'operator' => 'greater_than',
                    'value' => '10000',
                ],
            ],
        ]);

        $expected = 'Descripción contiene "Uber" Y Monto mayor que "10000"';
        $this->assertEquals($expected, $rule->conditions_text);
    }

    public function test_is_effective_attribute()
    {
        $effectiveRule = TransactionRule::factory()->create([
            'match_count' => 10,
            'is_active' => true,
        ]);
        $ineffectiveRule = TransactionRule::factory()->create([
            'match_count' => 0,
            'is_active' => true,
        ]);

        $this->assertTrue($effectiveRule->is_effective);
        $this->assertFalse($ineffectiveRule->is_effective);
    }

    public function test_matches_transaction_method()
    {
        $rule = TransactionRule::factory()->create([
            'conditions' => [
                [
                    'field' => 'description',
                    'operator' => 'contains',
                    'value' => 'Uber',
                ],
            ],
        ]);

        $matchingTransaction = Transaction::factory()->create([
            'description' => 'Viaje en Uber al aeropuerto',
        ]);

        $nonMatchingTransaction = Transaction::factory()->create([
            'description' => 'Compra en supermercado',
        ]);

        $this->assertTrue($rule->matchesTransaction($matchingTransaction));
        $this->assertFalse($rule->matchesTransaction($nonMatchingTransaction));
    }

    public function test_matches_transaction_with_multiple_conditions()
    {
        $rule = TransactionRule::factory()->create([
            'is_active' => true,
            'conditions' => [
                [
                    'field' => 'description',
                    'operator' => 'contains',
                    'value' => 'Uber',
                ],
                [
                    'field' => 'amount',
                    'operator' => 'greater_than',
                    'value' => '10000',
                ],
            ],
        ]);

        $fullyMatchingTransaction = Transaction::factory()->create([
            'description' => 'Viaje en Uber',
            'amount' => 15000,
        ]);

        $partiallyMatchingTransaction = Transaction::factory()->create([
            'description' => 'Viaje en Uber',
            'amount' => 5000,
        ]);

        $this->assertTrue($rule->matchesTransaction($fullyMatchingTransaction));
        $this->assertFalse($rule->matchesTransaction($partiallyMatchingTransaction));
    }

    public function test_test_against_transactions_method()
    {
        $category = Category::factory()->create();
        $rule = TransactionRule::factory()->create([
            'category_id' => $category->id,
            'conditions' => [
                [
                    'field' => 'description',
                    'operator' => 'contains',
                    'value' => 'Test',
                ],
            ],
        ]);

        // Create test transactions
        Transaction::factory()->create(['description' => 'Test transaction 1']);
        Transaction::factory()->create(['description' => 'Test transaction 2']);
        Transaction::factory()->create(['description' => 'Regular transaction']);

        $results = $rule->testAgainstTransactions(10);

        $this->assertArrayHasKey('matches', $results);
        $this->assertArrayHasKey('total_tested', $results);
        $this->assertArrayHasKey('match_rate', $results);
        $this->assertEquals(2, count($results['matches']));
        $this->assertEquals(66.67, round($results['match_rate'], 2));
    }

    public function test_effective_scope()
    {
        $effectiveRule = TransactionRule::factory()->create([
            'match_count' => 5,
            'is_active' => true,
        ]);
        $ineffectiveRule = TransactionRule::factory()->create([
            'match_count' => 0,
            'is_active' => true,
        ]);

        $effectiveRules = TransactionRule::effective()->get();

        $this->assertTrue($effectiveRules->contains($effectiveRule));
        $this->assertFalse($effectiveRules->contains($ineffectiveRule));
    }

    public function test_different_operators()
    {
        $operators = [
            'equals' => ['Exact Match', 'Exact Match', true],
            'equals' => ['Exact Match', 'Different', false],
            'starts_with' => ['Uber Trip', 'Uber', true],
            'starts_with' => ['Trip Uber', 'Uber', false],
            'ends_with' => ['Payment Visa', 'Visa', true],
            'ends_with' => ['Visa Payment', 'Visa', false],
            'greater_than' => [100, '50', true],
            'greater_than' => [30, '50', false],
            'less_than' => [30, '50', true],
            'less_than' => [100, '50', false],
        ];

        foreach ($operators as $operator => [$transactionValue, $ruleValue, $expectedResult]) {
            $field = is_numeric($transactionValue) ? 'amount' : 'description';

            $rule = TransactionRule::factory()->create([
                'conditions' => [
                    [
                        'field' => $field,
                        'operator' => $operator,
                        'value' => $ruleValue,
                    ],
                ],
            ]);

            $transaction = Transaction::factory()->create([
                $field => $transactionValue,
            ]);

            $this->assertEquals($expectedResult, $rule->matchesTransaction($transaction),
                "Failed for operator: {$operator}, transaction value: {$transactionValue}, rule value: {$ruleValue}");
        }
    }
}
