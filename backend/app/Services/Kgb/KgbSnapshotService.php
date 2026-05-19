<?php

namespace App\Services\Kgb;

use App\Models\KgbSnapshot;
use App\Models\RiwayatKgb;

class KgbSnapshotService
{
    public function store(RiwayatKgb $kgb, array $pegawaiData): KgbSnapshot
    {
        return KgbSnapshot::create([
            'riwayat_kgb_id' => $kgb->id,
            'data_json' => $pegawaiData,
        ]);
    }

    public function get(RiwayatKgb $kgb): ?KgbSnapshot
    {
        return $kgb->snapshot;
    }

    public function diff(RiwayatKgb $kgb, array $currentData): array
    {
        $snapshot = $this->get($kgb);
        if (!$snapshot) return [];

        $old = $snapshot->data_json;
        $diff = [];

        foreach ($currentData as $key => $value) {
            if (isset($old[$key]) && $old[$key] !== $value) {
                $diff[$key] = ['old' => $old[$key], 'new' => $value];
            }
        }

        return $diff;
    }
}
