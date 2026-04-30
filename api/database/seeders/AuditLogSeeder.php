<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $tables = ['riwayat_kgb', 'riwayat_pmk'];
        $actions = ['create', 'update', 'delete'];

        for ($i = 0; $i < 20; $i++) {
            AuditLog::create([
                'table_name' => fake()->randomElement($tables),
                'record_id' => fake()->numberBetween(1, 20),
                'action' => fake()->randomElement($actions),
                'old_data' => $i % 3 === 0 ? ['status' => 'draft'] : null,
                'new_data' => ['status' => fake()->randomElement(['diajukan', 'diverifikasi', 'disetujui'])],
            ]);
        }
    }
}
