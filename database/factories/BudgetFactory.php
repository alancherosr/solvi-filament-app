<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-6 months', '+3 months');
        $period = $this->faker->randomElement(['monthly', 'quarterly', 'yearly']);

        $endDate = match ($period) {
            'monthly' => (clone $startDate)->modify('+1 month -1 day'),
            'quarterly' => (clone $startDate)->modify('+3 months -1 day'),
            'yearly' => (clone $startDate)->modify('+1 year -1 day'),
        };

        return [
            'category_id' => Category::factory()->expense(),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'alert_threshold' => $this->faker->numberBetween(70, 90),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function monthly(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();

            return [
                'period' => 'monthly',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        });
    }

    public function quarterly(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = now()->startOfQuarter();
            $endDate = now()->endOfQuarter();

            return [
                'period' => 'quarterly',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        });
    }

    public function yearly(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = now()->startOfYear();
            $endDate = now()->endOfYear();

            return [
                'period' => 'yearly',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        });
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

    public function currentPeriod(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();

            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        });
    }
}
