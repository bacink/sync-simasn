<?php

namespace App\Services\Kgb;

use App\Models\KgbSnapshot;
use App\Models\RiwayatKgb;
use Illuminate\Support\Facades\Log;

/**
 * Captures and stores an immutable snapshot of ASN employee data at the time
 * a KGB record is created. This ensures historical accuracy regardless of
 * future changes to the employee's record in SIM-ASN.
 */
class KgbSnapshotService
{
    /**
     * Store an immutable snapshot of employee data for a KGB record.
     * The snapshot links to the regulation used for salary calculation,
     * and records the base salary (gaji_pokok) at snapshot time.
     */
    public function store(
        RiwayatKgb $kgb,
        array $pegawaiData,
        int $peraturanId,
        float $gajiPokok,
    ): KgbSnapshot {
        $snapshot = KgbSnapshot::create([
            'riwayat_kgb_id' => $kgb->id,
            'peraturan_id' => $peraturanId,
            'jabatan_nama' => $pegawaiData['jabatan_nama'] ?? null,
            'unit_kerja' => $pegawaiData['unit_kerja'] ?? null,
            'nama_skpd' => $pegawaiData['nama_skpd'] ?? null,
            'golongan' => $pegawaiData['golongan'] ?? null,
            'masa_kerja_tahun' => $kgb->masa_kerja_tahun,
            'masa_kerja_bulan' => $kgb->masa_kerja_bulan,
            'gaji_pokok' => $gajiPokok,
            'data_json' => $pegawaiData,
        ]);

        Log::info('KgbSnapshotService: Snapshot created', [
            'riwayat_kgb_id' => $kgb->id,
            'peraturan_id' => $peraturanId,
        ]);

        return $snapshot;
    }

    public function getSnapshot(int $riwayatKgbId): ?KgbSnapshot
    {
        return KgbSnapshot::query()
            ->where('riwayat_kgb_id', $riwayatKgbId)
            ->first();
    }
}
