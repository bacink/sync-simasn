<?php

namespace App\Http\Controllers;

use App\Enums\JenisAsn;
use App\Http\Resources\RefGajiAsnResource;
use App\Http\Resources\RefGolonganResource;
use App\Http\Resources\RefPeraturanResource;
use App\Models\RefGajiAsn;
use App\Models\RefGolongan;
use App\Models\RefPeraturan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RefGajiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $jenisAsn = $request->query('jenis_asn');

        if ($jenisAsn && !in_array($jenisAsn, array_column(JenisAsn::cases(), 'value'))) {
            return $this->errorResponse('Nilai jenis_asn tidak valid.', ['jenis_asn' => ['Nilai yang valid: pns, pppk']], 422);
        }

        $query = RefGajiAsn::query()
            ->with('peraturan')
            ->orderBy('golongan')
            ->orderBy('sub_golongan')
            ->orderBy('masa_kerja');

        if ($jenisAsn) {
            $query->where('jenis_asn', $jenisAsn);
        }

        $peraturan = RefPeraturan::orderBy('effective_date', 'desc')
            ->whereYear('effective_date', '>=', 2024)
            ->get();

        $golongan = RefGolongan::query()
            ->when($jenisAsn, fn($q) => $q->where('jenis_asn', $jenisAsn))
            ->whereNotNull('sub_golongan')
            ->orderBy('urutan')
            ->get();

        $items = $query->get();

        return $this->listResponse('Daftar referensi gaji', [
            'items' => RefGajiAsnResource::collection($items),
            'golongan' => RefGolonganResource::collection($golongan),
            'peraturan' => RefPeraturanResource::collection($peraturan),
        ]);
    }

    public function show(Request $request, string $golongan, string $masaKerja): JsonResponse
    {
        $jenisAsn = $request->query('jenis_asn', 'pns');

        $items = RefGajiAsn::query()
            ->where('jenis_asn', $jenisAsn)
            ->where('golongan', $golongan)
            ->where('masa_kerja', (int) $masaKerja)
            ->with('peraturan')
            ->orderByDesc('peraturan_id')
            ->get();

        if ($items->isEmpty()) {
            return $this->errorResponse("Data gaji untuk Golongan {$golongan}, MK {$masaKerja} tidak ditemukan.", [], 404);
        }

        return $this->successResponse('Detail gaji', RefGajiAsnResource::collection($items));
    }
}
