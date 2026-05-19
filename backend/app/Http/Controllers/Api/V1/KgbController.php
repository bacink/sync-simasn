<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\KgbStatus;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kgb\ApproveKgbRequest;
use App\Http\Requests\Kgb\RejectKgbRequest;
use App\Http\Requests\Kgb\StoreKgbRequest;
use App\Http\Requests\Kgb\VerifyKgbRequest;
use App\Models\RiwayatKgb;
use App\Services\Kgb\KgbService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KgbController extends Controller
{
    public function __construct(
        private readonly KgbService $kgbService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'opd_id', 'pegawai_id', 'tahun',
            'search', 'sort', 'dir', 'per_page',
        ]);

        $paginator = $this->kgbService->list($filters);

        $items = collect($paginator->items())->map(fn ($kgb) => $this->formatKgb($kgb))->toArray();

        return ApiResponse::paginated($paginator, $items);
    }

    public function store(StoreKgbRequest $request): JsonResponse
    {
        $kgb = $this->kgbService->generateDraft(
            $request->validated('pegawai_id'),
            $request->validated('pmk_id'),
        );

        return ApiResponse::created($this->formatKgb($kgb));
    }

    public function show(int $id): JsonResponse
    {
        $kgb = $this->kgbService->find($id);

        return ApiResponse::success($this->formatKgb($kgb));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $kgb = $this->kgbService->update($id, $request->all());

        return ApiResponse::success($this->formatKgb($kgb));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->kgbService->delete($id);

        return ApiResponse::success(null, ['message' => 'Data KGB berhasil dihapus']);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $kgb = $this->kgbService->submit($id, $request->string('notes')?->toString());

        return ApiResponse::success($this->formatKgb($kgb));
    }

    public function verify(VerifyKgbRequest $request, int $id): JsonResponse
    {
        $kgb = $this->kgbService->verify(
            $id,
            $request->validated('action'),
            $request->validated('notes'),
        );

        return ApiResponse::success($this->formatKgb($kgb));
    }

    public function approve(ApproveKgbRequest $request, int $id): JsonResponse
    {
        $kgb = $this->kgbService->approve($id, $request->validated('notes'));

        return ApiResponse::success($this->formatKgb($kgb));
    }

    public function reject(RejectKgbRequest $request, int $id): JsonResponse
    {
        $kgb = $this->kgbService->reject($id, $request->validated('reason'));

        return ApiResponse::success($this->formatKgb($kgb));
    }

    public function snapshot(int $id): JsonResponse
    {
        $snapshot = $this->kgbService->getSnapshot($id);

        return ApiResponse::success($snapshot);
    }

    public function document(int $id): JsonResponse
    {
        $url = $this->kgbService->getDocumentUrl($id);

        return ApiResponse::success(['url' => $url]);
    }

    /**
     * Format a RiwayatKgb model into a consistent array.
     */
    private function formatKgb(RiwayatKgb $kgb): array
    {
        return [
            'id' => $kgb->id,
            'pegawai_id' => $kgb->pegawai_id,
            'status' => $kgb->status->value,
            'golongan' => $kgb->golongan,
            'masa_kerja_tahun' => $kgb->masa_kerja_tahun,
            'masa_kerja_bulan' => $kgb->masa_kerja_bulan,
            'gaji_lama' => (int) $kgb->gaji_lama,
            'gaji_baru' => (int) $kgb->gaji_baru,
            'tmt_kgb_lama' => $kgb->tmt_kgb_lama?->format('Y-m-d'),
            'tmt_kgb_baru' => $kgb->tmt_kgb_baru->format('Y-m-d'),
            'snapshot_id' => $kgb->snapshot?->id,
            'created_at' => $kgb->created_at->toIso8601String(),
        ];
    }
}