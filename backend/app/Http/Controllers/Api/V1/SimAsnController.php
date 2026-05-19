<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\SimAsn\SimAsnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimAsnController extends Controller
{
    public function __construct(
        private readonly SimAsnService $simAsnService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $params = $request->only([
            'search', 'opd_id', 'page', 'per_page',
        ]);

        $data = $this->simAsnService->getPegawaiList($params);

        return ApiResponse::success($data);
    }

    public function show(int $id): JsonResponse
    {
        $pegawai = $this->simAsnService->getPegawai($id);

        return ApiResponse::success($pegawai);
    }

    public function golongan(int $id): JsonResponse
    {
        $riwayat = $this->simAsnService->getRiwayatGolongan($id);

        return ApiResponse::success($riwayat);
    }

    public function jabatan(int $id): JsonResponse
    {
        $riwayat = $this->simAsnService->getRiwayatJabatan($id);

        return ApiResponse::success($riwayat);
    }
}