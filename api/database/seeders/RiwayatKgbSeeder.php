<?php

namespace Database\Seeders;

use App\Enums\KgbStatus;
use App\Enums\KgbType;
use App\Models\RiwayatKgb;
use App\Models\RiwayatPmk;
use Illuminate\Database\Seeder;

class RiwayatKgbSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();
        $statuses = array_column(KgbStatus::cases(), 'value');
        $types = array_column(KgbType::cases(), 'value');

        $names = [
            'Dr. H. Budi Santoso, M.Si.',
            'Dr. Rina Marlina, M.Kom.',
            'Dr. Agus Wijaya, M.Hum.',
            'Ir. Siti Nurhaliza, M.T.',
            'Dr. Ahmad Dahlan, M.Pdi.',
        ];

        foreach ($names as $name) {
            $mkThn = $faker->numberBetween(5, 20);
            $mkBln = $faker->numberBetween(0, 11);
            $gajiLama = $faker->randomFloat(2, 2000000, 8000000);
            $gajiBaru = $gajiLama * 1.05;

            RiwayatKgb::firstOrCreate(
                ['nip' => $faker->numerify('####################')],
                [
                    'pegawai_id' => $faker->uuid(),
                    'nip' => $faker->numerify('####################'),
                    'nama' => $name,
                    'golongan' => $faker->randomElement(['I', 'II', 'III', 'IV']),
                    'masa_kerja_tahun' => $mkThn,
                    'masa_kerja_bulan' => $mkBln,
                    'gaji_lama' => $gajiLama,
                    'gaji_baru' => $gajiBaru,
                    'tmt_kgb' => $faker->date(),
                    'nomor_sk' => $faker->numerify('###/SK/KGB/2024'),
                    'tanggal_sk' => $faker->date(),
                    'jenis_kgb' => $faker->randomElement($types),
                    'status' => $faker->randomElement($statuses),
                ]
            );
        }

        RiwayatKgb::factory()->count(15)->create();
    }
}
