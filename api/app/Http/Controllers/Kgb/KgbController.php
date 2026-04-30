<?php

namespace App\Http\Controllers\Kgb;

use App\DTOs\KgbGenerateDTO as GenerateKgbDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Kgb\GenerateKgbRequest;
use App\Services\Kgb\KgbService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KgbController extends ApiController
{
    public function __construct(
        protected KgbService $kgbService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'pegawai_id', 'nip', 'tahun']);
            $list = $this->kgbService->getList($filters);

            return $this->listResponse('Data KGB berhasil diambil', $list, $list->count());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(GenerateKgbRequest $request): JsonResponse
    {
        try {
            $dto = GenerateKgbDTO::fromRequest($request->validated());
            $kgb = $this->kgbService->generateDraft($dto->pegawaiId);

            return $this->successResponse('Draft KGB berhasil dibuat', $kgb, 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $kgb = $this->kgbService->findOrFail($id);

            return $this->successResponse('Data KGB berhasil diambil', $kgb);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function submit(int $id): JsonResponse
    {
        try {
            $kgb = $this->kgbService->submit($id);

            return $this->successResponse('KGB berhasil diajukan', $kgb);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function verify(int $id): JsonResponse
    {
        try {
            $catatan = request()->input('catatan');
            $kgb = $this->kgbService->verify($id, $catatan);

            return $this->successResponse('KGB berhasil diverifikasi', $kgb);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function approve(int $id): JsonResponse
    {
        try {
            $kgb = $this->kgbService->approve($id);

            return $this->successResponse('KGB berhasil disetujui', $kgb);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function reject(int $id): JsonResponse
    {
        try {
            $catatan = request()->input('catatan', 'Ditolak');

            $kgb = $this->kgbService->reject($id, $catatan);

            return $this->successResponse('KGB ditolak', $kgb);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
