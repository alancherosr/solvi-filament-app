<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\TransactionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionRuleFactory extends Factory
{
    protected $model = TransactionRule::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'category_id' => Category::factory(),
            'conditions' => [
                [
                    'field' => $this->faker->randomElement(['description', 'amount', 'reference_number']),
                    'operator' => $this->faker->randomElement(['contains', 'equals', 'starts_with', 'ends_with']),
                    'value' => $this->faker->word(),
                ],
            ],
            'priority' => $this->faker->numberBetween(0, 10),
            'match_count' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(90),
        ];
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

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $this->faker->numberBetween(5, 10),
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $this->faker->numberBetween(0, 4),
        ]);
    }

    public function effective(): static
    {
        return $this->state(fn (array $attributes) => [
            'match_count' => $this->faker->numberBetween(1, 100),
        ]);
    }

    public function ineffective(): static
    {
        return $this->state(fn (array $attributes) => [
            'match_count' => 0,
        ]);
    }

    public function withMultipleConditions(): static
    {
        return $this->state(fn (array $attributes) => [
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
    }

    public function containsDescription(string $text): static
    {
        return $this->state(fn (array $attributes) => [
            'conditions' => [
                [
                    'field' => 'description',
                    'operator' => 'contains',
                    'value' => $text,
                ],
            ],
        ]);
    }

    public function amountGreaterThan(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'conditions' => [
                [
                    'field' => 'amount',
                    'operator' => 'greater_than',
                    'value' => (string) $amount,
                ],
            ],
        ]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }
}
