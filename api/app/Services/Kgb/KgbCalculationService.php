<?php

namespace App\Services\Kgb;

use App\Enums\JenisAsn;
use App\Models\RefGajiAsn;
use App\Models\RefPeraturan;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service responsible for pure salary calculation logic.
 * This service is stateless and acts as a lookup engine for the salary reference table.
 */
class KgbCalculationService
{
    /**
     * Look up the salary based on ASN type, grade, and years of service.
     *
     * @param string|JenisAsn $jenisAsn The type of ASN ('pns' or 'pppk').
     * @param string $golongan The grade/rank (e.g., 'III').
     * @param int $masaKerja The years of work period (0-32).
     * @param string|null $subGolongan The sub-grade (e.g., 'a').
     * @param int|null $peraturanId The specific regulation ID. If null, the latest regulation will be used.
     * @return RefGajiAsn
     *
     * @throws ModelNotFoundException
     */
    public function calculate(
        string|JenisAsn $jenisAsn,
        string $golongan,
        int $masaKerja,
        ?string $subGolongan = null,
        ?int $peraturanId = null
    ): RefGajiAsn {
        $jenisAsnValue = $jenisAsn instanceof JenisAsn ? $jenisAsn->value : $jenisAsn;

        if (!$peraturanId) {
            $peraturanId = RefPeraturan::query()
                ->orderByDesc('tahun')
                ->orderByDesc('id')
                ->value('id');
        }

        return RefGajiAsn::query()
            ->where('peraturan_id', $peraturanId)
            ->where('jenis_asn', $jenisAsnValue)
            ->where('golongan', $golongan)
            ->when($subGolongan, fn($q) => $q->where('sub_golongan', $subGolongan))
            ->where('masa_kerja', $masaKerja)
            ->firstOrFail();
    }
}
