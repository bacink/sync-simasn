<?php

namespace Database\Seeders;

use App\Enums\KgbStatus;
use App\Enums\KgbType;
use App\Models\RiwayatPmk;
use Illuminate\Database\Seeder;

class RiwayatPmkSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();
        $statuses = array_column(KgbStatus::cases(), 'value');

        $names = [
            'Dr. H. Budi Santoso, M.Si.',
            'Dr. Rina Marlina, M.Kom.',
            'Dr. Agus Wijaya, M.Hum.',
            'Ir. Siti Nurhaliza, M.T.',
            'Dr. Ahmad Dahlan, M.Pdi.',
            'Prof. Dr. H. Muhammad Yusuf, M.Sc.',
            'Dr. Titi Harmoko, M.M.',
            'Ir. H. Abdul Karim, Ph.D.',
        ];

        foreach ($names as $i => $name) {
            $lamaThn = $faker->numberBetween(5, 15);
            $lamaBln = $faker->numberBetween(0, 11);

            RiwayatPmk::firstOrCreate(
                ['nip' => $faker->numerify('####################')],
                [
                    'pegawai_id' => $faker->uuid(),
                    'nip' => $faker->numerify('####################'),
                    'nama' => $name,
                    'masa_kerja_lama_tahun' => $lamaThn,
                    'masa_kerja_lama_bulan' => $lamaBln,
                    'masa_kerja_baru_tahun' => $lamaThn + 2,
                    'masa_kerja_baru_bulan' => $lamaBln,
                    'dasar_pmk' => 'SKep/PERTEK跟着跟着跟着跟着',
                    'nomor_sk' => $faker->numerify('###/SK/PMK/2024'),
                    'tanggal_sk' => $faker->date(),
                ]
            );
        }

        // Random additional records
        RiwayatPmk::factory()->count(12)->create();
    }
}
