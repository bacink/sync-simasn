<?php

namespace App\Http\Controllers\Api\V1\SimAsn;

use App\Http\Controllers\Controller;
use App\Services\SimAsn\SimAsnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    public function __construct(
        private readonly SimAsnService $simAsnService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $params = [
            'page' => $request->integer('page', 1),
            'per_page' => $request->integer('per_page', 20),
        ];

        if ($request->filled('opd_id')) {
            $params['opd_id'] = $request->integer('opd_id');
        }
        if ($request->filled('search')) {
            $params['search'] = $request->string('search');
        }

        $data = $this->simAsnService->getPegawaiList($params);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $pegawai = $this->simAsnService->getPegawai($id);

        return response()->json([
            'success' => true,
            'data' => $pegawai,
        ]);
    }

    public function golongan(int $id): JsonResponse
    {
        $riwayat = $this->simAsnService->getRiwayatGolongan($id);

        return response()->json([
            'success' => true,
            'data' => $riwayat,
        ]);
    }

    public function jabatan(int $id): JsonResponse
    {
        $riwayat = $this->simAsnService->getRiwayatJabatan($id);

        return response()->json([
            'success' => true,
            'data' => $riwayat,
        ]);
    }
}
