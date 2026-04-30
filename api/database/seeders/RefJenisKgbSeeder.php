<?php

namespace Database\Seeders;

use App\Models\RefJenisKgb;
use Illuminate\Database\Seeder;

class RefJenisKgbSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'kode' => 'reguler',
                'nama' => 'KGB Reguler',
                'deskripsi' => 'Kenaikan Gaji Berkala reguler setiap 2 tahun',
            ],
            [
                'kode' => 'penyesuaian',
                'nama' => 'KGB Penyesuaian',
                'deskripsi' => 'Kenaikan Gaji Berkala akibat penyesuaian gaji/pangkat',
            ],
        ];

        foreach ($rows as $row) {
            RefJenisKgb::firstOrCreate(['kode' => $row['kode']], $row);
        }
    }
}
