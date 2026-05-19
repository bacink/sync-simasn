<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pmk\StorePmkRequest;
use App\Http\Requests\Pmk\UpdatePmkRequest;
use App\Services\Pmk\PmkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PmkController extends Controller
{
    public function __construct(
        private readonly PmkService $pmkService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'pegawai_id', 'tahun', 'status', 'search', 'sort', 'dir', 'per_page',
        ]);

        $paginator = $this->pmkService->list($filters);

        $items = collect($paginator->items())->map(fn ($pmk) => [
            'id' => $pmk->id,
            'pegawai_id' => $pmk->pegawai_id,
            'pegawai_nama' => $pmk->pegawai_nama,
            'pegawai_nip' => $pmk->pegawai_nip,
            'status' => $pmk->status->value,
            'no_sk' => $pmk->no_sk,
            'tanggal_sk' => $pmk->tanggal_sk?->format('Y-m-d'),
            'masa_kerja_lama_tahun' => $pmk->masa_kerja_lama_tahun,
            'masa_kerja_lama_bulan' => $pmk->masa_kerja_lama_bulan,
            'masa_kerja_baru_tahun' => $pmk->masa_kerja_baru_tahun,
            'masa_kerja_baru_bulan' => $pmk->masa_kerja_baru_bulan,
            'opd_id' => $pmk->opd_id,
            'created_at' => $pmk->created_at->toIso8601String(),
        ])->toArray();

        return ApiResponse::paginated($paginator, $items);
    }

    public function store(StorePmkRequest $request): JsonResponse
    {
        $pmk = $this->pmkService->create($request->validated());

        return ApiResponse::created([
            'id' => $pmk->id,
            'pegawai_id' => $pmk->pegawai_id,
            'pegawai_nama' => $pmk->pegawai_nama,
            'pegawai_nip' => $pmk->pegawai_nip,
            'status' => $pmk->status->value,
            'no_sk' => $pmk->no_sk,
            'tanggal_sk' => $pmk->tanggal_sk?->format('Y-m-d'),
            'masa_kerja_lama_tahun' => $pmk->masa_kerja_lama_tahun,
            'masa_kerja_lama_bulan' => $pmk->masa_kerja_lama_bulan,
            'masa_kerja_baru_tahun' => $pmk->masa_kerja_baru_tahun,
            'masa_kerja_baru_bulan' => $pmk->masa_kerja_baru_bulan,
            'created_at' => $pmk->created_at->toIso8601String(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $pmk = $this->pmkService->find($id);

        return ApiResponse::success([
            'id' => $pmk->id,
            'pegawai_id' => $pmk->pegawai_id,
            'pegawai_nama' => $pmk->pegawai_nama,
            'pegawai_nip' => $pmk->pegawai_nip,
            'status' => $pmk->status->value,
            'no_sk' => $pmk->no_sk,
            'tanggal_sk' => $pmk->tanggal_sk?->format('Y-m-d'),
            'masa_kerja_lama_tahun' => $pmk->masa_kerja_lama_tahun,
            'masa_kerja_lama_bulan' => $pmk->masa_kerja_lama_bulan,
            'masa_kerja_baru_tahun' => $pmk->masa_kerja_baru_tahun,
            'masa_kerja_baru_bulan' => $pmk->masa_kerja_baru_bulan,
            'keterangan' => $pmk->keterangan,
            'opd_id' => $pmk->opd_id,
            'created_at' => $pmk->created_at->toIso8601String(),
            'updated_at' => $pmk->updated_at->toIso8601String(),
        ]);
    }

    public function update(UpdatePmkRequest $request, int $id): JsonResponse
    {
        $pmk = $this->pmkService->update($id, $request->validated());

        return ApiResponse::success([
            'id' => $pmk->id,
            'pegawai_id' => $pmk->pegawai_id,
            'pegawai_nama' => $pmk->pegawai_nama,
            'pegawai_nip' => $pmk->pegawai_nip,
            'status' => $pmk->status->value,
            'no_sk' => $pmk->no_sk,
            'tanggal_sk' => $pmk->tanggal_sk?->format('Y-m-d'),
            'masa_kerja_lama_tahun' => $pmk->masa_kerja_lama_tahun,
            'masa_kerja_lama_bulan' => $pmk->masa_kerja_lama_bulan,
            'masa_kerja_baru_tahun' => $pmk->masa_kerja_baru_tahun,
            'masa_kerja_baru_bulan' => $pmk->masa_kerja_baru_bulan,
            'updated_at' => $pmk->updated_at->toIso8601String(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->pmkService->delete($id);

        return ApiResponse::success(null, ['message' => 'Data PMK berhasil dihapus']);
    }
}