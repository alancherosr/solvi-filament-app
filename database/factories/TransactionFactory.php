<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['income', 'expense', 'transfer']);
        $amount = $this->faker->randomFloat(2, 10, 1000);

        return [
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'amount' => $amount,
            'description' => $this->faker->sentence(3),
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'type' => $type,
            'reference_number' => $this->faker->optional()->numerify('REF-########'),
            'notes' => $this->faker->optional()->sentence(),
            'is_reconciled' => $this->faker->boolean(70),
            'transfer_to_account_id' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
            'category_id' => Category::factory()->income(),
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'category_id' => Category::factory()->expense(),
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'transfer',
            'transfer_to_account_id' => Account::factory(),
        ]);
    }

    public function reconciled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_reconciled' => true,
        ]);
    }

    public function unreconciled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_reconciled' => false,
        ]);
    }

    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_date' => $this->faker->dateTimeBetween('first day of this month', 'now'),
        ]);
    }

    public function thisYear(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_date' => $this->faker->dateTimeBetween('first day of january this year', 'now'),
        ]);
    }
}
