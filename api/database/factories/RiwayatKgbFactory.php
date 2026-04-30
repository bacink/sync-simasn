<?php

namespace Database\Factories;

use App\Enums\KgbStatus;
use App\Enums\KgbType;
use App\Models\RefGolongan;
use App\Models\RefPeraturan;
use App\Models\RiwayatKgb;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatKgb>
 */
class RiwayatKgbFactory extends Factory
{
    public function definition(): array
    {
        $golongan = RefGolongan::inRandomOrder()->first();
        $peraturan = RefPeraturan::inRandomOrder()->first();

        return [
            'pegawai_id' => fake()->uuid(),
            'golongan_id' => $golongan?->id,
            'peraturan_id' => $peraturan?->id,
            'masa_kerja_tahun' => fake()->numberBetween(0, 32),
            'masa_kerja_bulan' => fake()->numberBetween(0, 11),
            'gaji_lama' => fake()->randomFloat(2, 1000000, 20000000),
            'gaji_baru' => fake()->randomFloat(2, 1100000, 25000000),
            'tmt_kgb' => fake()->date(),
            'nomor_sk' => fake()->numerify('###/SK/KGB/####'),
            'tanggal_sk' => fake()->date(),
            'jenis_kgb' => fake()->randomElement(array_column(KgbType::cases(), 'value')),
            'status' => fake()->randomElement(array_column(KgbStatus::cases(), 'value')),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KgbStatus::Draft->value,
        ]);
    }

    public function disetujui(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KgbStatus::Disetujui->value,
        ]);
    }
}