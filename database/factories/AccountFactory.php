<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true).' Account',
            'type' => $this->faker->randomElement(['checking', 'savings', 'credit_card', 'cash', 'investment']),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'currency' => $this->faker->randomElement(['COP', 'USD', 'EUR']),
            'account_number' => $this->faker->numerify('##########'),
            'description' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function checking(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'checking',
        ]);
    }

    public function savings(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'savings',
        ]);
    }

    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'credit_card',
            'balance' => $this->faker->randomFloat(2, -5000, 0),
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
}
