<?php

namespace Database\Seeders;

use App\Models\RefStatusKgb;
use Illuminate\Database\Seeder;

class RefStatusKgbSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'kode' => 'draft',
                'nama' => 'Draft',
                'deskripsi' => 'Belum diajukan, masih bisa diedit',
                'urutan' => 1,
                'is_final' => false,
                'allow_edit' => true,
            ],
            [
                'kode' => 'diajukan',
                'nama' => 'Diajukan',
                'deskripsi' => 'Sudah diajukan ke verifikator',
                'urutan' => 2,
                'is_final' => false,
                'allow_edit' => false,
            ],
            [
                'kode' => 'diverifikasi',
                'nama' => 'Diverifikasi',
                'deskripsi' => 'Telah diverifikasi oleh verifikator',
                'urutan' => 3,
                'is_final' => false,
                'allow_edit' => false,
            ],
            [
                'kode' => 'disetujui',
                'nama' => 'Disetujui',
                'deskripsi' => 'Final — disetujui oleh admin',
                'urutan' => 4,
                'is_final' => true,
                'allow_edit' => false,
            ],
            [
                'kode' => 'ditolak',
                'nama' => 'Ditolak',
                'deskripsi' => 'Ditolak pada tahap verifikasi atau persetujuan',
                'urutan' => 5,
                'is_final' => true,
                'allow_edit' => false,
            ],
        ];

        foreach ($rows as $row) {
            RefStatusKgb::firstOrCreate(['kode' => $row['kode']], $row);
        }
    }
}
