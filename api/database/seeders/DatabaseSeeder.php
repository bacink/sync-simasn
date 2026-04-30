<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RefGolonganSeeder::class,
            RefPeraturanSeeder::class,
            RefGajiPnsSeeder::class,
            RefGajiPppkSeeder::class,
            RefStatusKgbSeeder::class,
            RefJenisKgbSeeder::class,
            UserSeeder::class,
            RiwayatPmkSeeder::class,
            RiwayatKgbSeeder::class,
            KgbApprovalSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}