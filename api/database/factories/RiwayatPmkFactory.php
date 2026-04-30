<?php

namespace Database\Factories;

use App\Models\RiwayatPmk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatPmk>
 */
class RiwayatPmkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pegawai_id' => fake()->uuid(),
            'nip' => fake()->numerify('####################'), // 20 digits
            'nama' => fake()->name(),
            'masa_kerja_lama_tahun' => fake()->numberBetween(0, 40),
            'masa_kerja_lama_bulan' => fake()->numberBetween(0, 11),
            'masa_kerja_baru_tahun' => fake()->numberBetween(1, 45),
            'masa_kerja_baru_bulan' => fake()->numberBetween(0, 11),
            'dasar_pmk' => fake()->sentence(),
            'nomor_sk' => fake()->numerify('###/SK/####'),
            'tanggal_sk' => fake()->date(),
        ];
    }
}