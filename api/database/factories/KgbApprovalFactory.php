<?php

namespace Database\Factories;

use App\Models\KgbApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KgbApproval>
 */
class KgbApprovalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role' => fake()->randomElement(['operator', 'verifikator', 'admin']),
            'step_order' => fake()->numberBetween(1, 3),
            'is_required' => true,
            'catatan' => fake()->optional()->sentence(),
        ];
    }

    public function verifikator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'verifikator',
            'step_order' => 2,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'step_order' => 3,
        ]);
    }
}