<?php

namespace App\Services\Kgb;

use App\Exceptions\ApiError;
use App\Models\RefGajiAsn;

class KgbCalculationService
{
    public function lookupGaji(string $golongan, int $masaKerjaTahun): ?RefGajiAsn
    {
        return RefGajiAsn::findByGolonganAndMasaKerja($golongan, $masaKerjaTahun);
    }

    public function calculateGajiBaru(string $golongan, int $masaKerjaTahun, ?int $masaKerjaBulan = 0): array
    {
        $masaKerjaTotalBulan = ($masaKerjaTahun * 12) + $masaKerjaBulan;
        $masaKerjaBaruBulan = $masaKerjaTotalBulan + 24;

        $masaKerjaBaruTahun = intdiv($masaKerjaBaruBulan, 12);
        $sisaBulan = $masaKerjaBaruBulan % 12;

        $gajiRef = $this->lookupGaji($golongan, $masaKerjaBaruTahun);

        if (!$gajiRef) {
            throw ApiError::refGajiNotFound($golongan, $masaKerjaBaruTahun);
        }

        return [
            'masa_kerja_tahun' => $masaKerjaBaruTahun,
            'masa_kerja_bulan' => $sisaBulan,
            'gaji' => (int) $gajiRef->gaji,
        ];
    }

    public function calculateTmtBaru(\DateTimeInterface $tmtLama): string
    {
        return $tmtLama->modify('+2 years')->format('Y-m-d');
    }

    public function formatMasaKerja(int $tahun, int $bulan): string
    {
        return "{$tahun} tahun {$bulan} bulan";
    }
}
