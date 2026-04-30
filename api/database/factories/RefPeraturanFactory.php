<?php

namespace Database\Factories;

use App\Models\RefPeraturan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RefPeraturan>
 */
class RefPeraturanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'jenis' => fake()->randomElement(['PERATURAN PEMERINTAH', 'PERATURAN PRESIDEN', 'UNDANG-UNDANG']),
            'nomor' => fake()->numerify('#/#'),
            'tahun' => fake()->year(),
            'effective_date' => fake()->dateTimeBetween('-3 years', 'now'),
            'nama' => fake()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => now()->startOfYear(),
        ]);
    }
}
