<?php

namespace App\Http\Controllers;

use App\Services\SimAsn\SimAsnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimAsnController extends ApiController
{
    public function __construct(
        protected SimAsnService $simAsnService
    ) {}

    /**
     * Proxy search request to SIM-ASN or get detail with history.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // If pegawai_id is provided, fetch detail with histories
            $pegawaiId = $request->query('pegawai_id');
            if ($pegawaiId) {
                return $this->successResponse('Data detail pegawai berhasil diambil', [
                    'pegawai' => $this->simAsnService->getPegawai($pegawaiId),
                    'riwayat_golongan' => $this->simAsnService->getRiwayatGolongan($pegawaiId),
                    'riwayat_jabatan' => $this->simAsnService->getRiwayatJabatan($pegawaiId),
                ]);
            }

            // Otherwise, perform search
            $query = $request->query('search', '');
            $data = $this->simAsnService->searchPegawai($query);

            return $this->successResponse('Data pegawai berhasil diambil dari SIM-ASN', $data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Proxy detail request to SIM-ASN.
     */
    public function show(int|string $id): JsonResponse
    {
        try {
            $data = $this->simAsnService->getPegawai($id);
            return $this->successResponse('Detail pegawai berhasil diambil dari SIM-ASN', $data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
