<?php

namespace App\Http\Controllers\Pmk;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Pmk\CreatePmkRequest;
use App\Services\Pmk\PmkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PmkController extends ApiController
{
    public function __construct(
        protected PmkService $pmkService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['pegawai_id', 'nip']);
            $list = $this->pmkService->getList($filters);

            return $this->listResponse('Data PMK berhasil diambil', $list, $list->count());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(CreatePmkRequest $request): JsonResponse
    {
        try {
            $pmk = $this->pmkService->create($request->validated());

            return $this->successResponse('PMK berhasil dibuat', $pmk, 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $pmk = $this->pmkService->findOrFail($id);

            return $this->successResponse('Data PMK berhasil diambil', $pmk);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $pmk = $this->pmkService->update($id, $request->validated());

            return $this->successResponse('PMK berhasil diperbarui', $pmk);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}