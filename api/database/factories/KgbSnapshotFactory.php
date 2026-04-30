<?php

namespace Database\Factories;

use App\Models\KgbSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KgbSnapshot>
 */
class KgbSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'riwayat_kgb_id' => fake()->randomDigitNotNull(),
            'jabatan_nama' => fake()->jobTitle(),
            'unit_kerja' => fake()->word() . ' ' . fake()->word(),
            'nama_skpd' => 'BKPSDM ' . fake()->city(),
            'golongan' => fake()->randomElement(['I', 'II', 'III', 'IV']),
            'masa_kerja_tahun' => fake()->numberBetween(0, 40),
            'masa_kerja_bulan' => fake()->numberBetween(0, 11),
            'data_json' => [
                'pegawai' => [
                    'nip' => fake()->numerify('####################'),
                    'nama' => fake()->name(),
                    'pangkat' => fake()->word(),
                    'golongan' => fake()->randomElement(['I', 'II', 'III', 'IV'])
                ],
                'simasn_data' => [
                    'tmt_pangkat' => fake()->date(),
                    'tmt_jabatan' => fake()->date(),
                    'masa_kerja' => [
                        'tahun' => fake()->numberBetween(0, 40),
                        'bulan' => fake()->numberBetween(0, 11)
                    ]
                ]
            ],
        ];
    }
}