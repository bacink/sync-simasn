<?php

namespace Database\Seeders;

use App\Models\KgbApproval;
use App\Models\RiwayatKgb;
use App\Models\User;
use Illuminate\Database\Seeder;

class KgbApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $kgbRecords = RiwayatKgb::whereIn('status', ['diverifikasi', 'disetujui', 'ditolak'])
            ->with('kgbApprovals')
            ->get();

        $users = User::all();

        foreach ($kgbRecords as $kgb) {
            if ($kgb->kgbApprovals->count() > 0) {
                continue;
            }

            $verifikator = $users->where('role', 'verifikator')->first();
            if ($verifikator) {
                KgbApproval::firstOrCreate(
                    ['riwayat_kgb_id' => $kgb->id, 'user_id' => $verifikator->id],
                    [
                        'role' => 'verifikator',
                        'status' => $kgb->status,
                        'catatan' => fake()->sentence(),
                    ]
                );
            }

            if (in_array($kgb->status, ['disetujui', 'ditolak'])) {
                $admin = $users->where('role', 'admin')->first();
                if ($admin) {
                    KgbApproval::firstOrCreate(
                        ['riwayat_kgb_id' => $kgb->id, 'user_id' => $admin->id],
                        [
                            'role' => 'admin',
                            'status' => $kgb->status,
                            'catatan' => fake()->sentence(),
                        ]
                    );
                }
            }
        }
    }
}
