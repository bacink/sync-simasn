<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\KgbStatus;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\RiwayatKgb;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $stats = [
            'draft' => RiwayatKgb::byStatus(KgbStatus::DRAFT)->count(),
            'diajukan' => RiwayatKgb::byStatus(KgbStatus::DIAJUKAN)->count(),
            'diverifikasi' => RiwayatKgb::byStatus(KgbStatus::DIVERIFIKASI)->count(),
            'disetujui' => RiwayatKgb::byStatus(KgbStatus::DISETUJUI)->count(),
            'ditolak' => RiwayatKgb::byStatus(KgbStatus::DITOLAK)->count(),
            'total' => RiwayatKgb::count(),
        ];

        return ApiResponse::success($stats);
    }

    public function pending(): JsonResponse
    {
        $items = RiwayatKgb::query()
            ->with(['opd'])
            ->byStatus(KgbStatus::DIAJUKAN)
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get()
            ->map(fn ($kgb) => [
                'id' => $kgb->id,
                'pegawai_id' => $kgb->pegawai_id,
                'pegawai_nama' => $kgb->pegawai_nama,
                'pegawai_nip' => $kgb->pegawai_nip,
                'status' => $kgb->status->value,
                'golongan' => $kgb->golongan,
                'gaji_baru' => (int) $kgb->gaji_baru,
                'tmt_kgb_baru' => $kgb->tmt_kgb_baru->format('Y-m-d'),
                'opd_nama' => $kgb->opd?->nama,
                'created_at' => $kgb->created_at->toIso8601String(),
            ])
            ->toArray();

        return ApiResponse::success($items);
    }

    public function upcoming(): JsonResponse
    {
        $cutoff = Carbon::now()->addDays(60)->startOfDay();

        $items = RiwayatKgb::query()
            ->with(['opd'])
            ->where('tmt_kgb_baru', '<=', $cutoff)
            ->whereNotIn('status', [KgbStatus::DITOLAK])
            ->orderBy('tmt_kgb_baru', 'asc')
            ->limit(20)
            ->get()
            ->map(fn ($kgb) => [
                'id' => $kgb->id,
                'pegawai_id' => $kgb->pegawai_id,
                'pegawai_nama' => $kgb->pegawai_nama,
                'pegawai_nip' => $kgb->pegawai_nip,
                'status' => $kgb->status->value,
                'golongan' => $kgb->golongan,
                'gaji_baru' => (int) $kgb->gaji_baru,
                'tmt_kgb_baru' => $kgb->tmt_kgb_baru->format('Y-m-d'),
                'hari_menuju_tmt' => Carbon::now()->startOfDay()->diffInDays($kgb->tmt_kgb_baru->startOfDay(), false),
                'opd_nama' => $kgb->opd?->nama,
                'created_at' => $kgb->created_at->toIso8601String(),
            ])
            ->toArray();

        return ApiResponse::success($items);
    }
}