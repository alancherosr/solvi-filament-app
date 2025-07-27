<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringTransactionFactory extends Factory
{
    protected $model = RecurringTransaction::class;

    public function definition(): array
    {
        $frequency = $this->faker->randomElement(['daily', 'weekly', 'monthly', 'quarterly', 'yearly']);
        $isIncome = $this->faker->boolean(30);
        $amount = $this->faker->randomFloat(2, 50, 2000);

        if (! $isIncome) {
            $amount = -$amount; // Make expenses negative
        }

        $nextDueDate = $this->faker->dateTimeBetween('now', '+2 months');

        return [
            'account_id' => Account::factory(),
            'category_id' => Category::factory()->state(['type' => $isIncome ? 'income' : 'expense']),
            'amount' => $amount,
            'description' => $this->faker->sentence(3),
            'frequency' => $frequency,
            'next_due_date' => $nextDueDate,
            'end_date' => $this->faker->optional(70)->dateTimeBetween($nextDueDate, '+2 years'),
            'is_active' => $this->faker->boolean(90),
            'auto_process' => $this->faker->boolean(60),
            'last_processed_at' => $this->faker->optional(50)->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => abs($attributes['amount'] ?? $this->faker->randomFloat(2, 100, 3000)),
            'category_id' => Category::factory()->income(),
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => -abs($attributes['amount'] ?? $this->faker->randomFloat(2, 50, 1000)),
            'category_id' => Category::factory()->expense(),
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'daily',
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'weekly',
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
        ]);
    }

    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'quarterly',
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'yearly',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function autoProcess(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_process' => true,
        ]);
    }

    public function manualProcess(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_process' => false,
        ]);
    }

    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_due_date' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_due_date' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    public function notExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+2 years'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $endDate = $this->faker->dateTimeBetween('-1 year', '-1 day');

            return [
                'next_due_date' => $this->faker->dateTimeBetween('-2 years', $endDate),
                'end_date' => $endDate,
            ];
        });
    }
}
