<?php

namespace Database\Seeders;

use App\Models\RefPeraturan;
use Illuminate\Database\Seeder;

class RefPeraturanSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'jenis' => 'PP',
                'nomor' => '17',
                'tahun' => 2019,
                'effective_date' => '2019-01-01',
                'nama' => 'PP 17 Tahun 2019 tentang Perubahan Kedelapan Belas atas PP 6 Tahun 1983',
            ],
            [
                'jenis' => 'PP',
                'nomor' => '11',
                'tahun' => 2023,
                'effective_date' => '2023-05-01',
                'nama' => 'PP 11 Tahun 2023 tentang Perubahan atas PP 6 Tahun 1983 (Perbaikan Gaji)',
            ],
            [
                'jenis' => 'PP',
                'nomor' => '8',
                'tahun' => 2024,
                'effective_date' => '2024-01-01',
                'nama' => 'PP 8 Tahun 2024 tentang Perubahan Kedelapan atas PP 6 Tahun 1983',
            ],
            [
                'jenis' => 'PERPRES',
                'nomor' => '11',
                'tahun' => 2024,
                'effective_date' => '2024-06-01',
                'nama' => 'Peraturan Presiden Nomor 11 Tahun 2024 tentang Penyesuaian Gaji, Pensiun, dan Tunjangan',
            ],
            [
                'jenis' => 'KEP',
                'nomor' => '179',
                'tahun' => 2024,
                'effective_date' => '2024-06-01',
                'nama' => 'Keputusan Presiden tentang Kenaikan Gaji Berkala',
            ],
            [
                'jenis' => 'PP',
                'nomor' => '2',
                'tahun' => 2025,
                'effective_date' => '2025-01-01',
                'nama' => 'PP 2 Tahun 2025 tentang Penyesuaian Gaji PNS',
            ],
        ];

        foreach ($rows as $row) {
            RefPeraturan::firstOrCreate(
                [
                    'jenis' => $row['jenis'],
                    'nomor' => $row['nomor'],
                    'tahun' => $row['tahun'],
                ],
                $row
            );
        }
    }
}