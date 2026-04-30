<?php

namespace Database\Factories;

use App\Enums\JenisAsn;
use App\Models\RefGajiAsn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RefGajiAsn>
 */
class RefGajiAsnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenis_asn' => fake()->randomElement(array_column(JenisAsn::cases(), 'value')),
            'peraturan_id' => fake()->randomDigitNotNull(),
            'golongan' => fake()->randomElement(['I', 'II', 'III', 'IV']),
            'sub_golongan' => fake()->randomElement(['a', 'b', 'c']),
            'masa_kerja' => fake()->numberBetween(0, 40),
            'gaji' => fake()->randomFloat(2, 1000000, 20000000),
        ];
    }
}