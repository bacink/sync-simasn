<?php

namespace App\Http\Controllers\SimAsn;

use App\Http\Controllers\Controller;
use App\Models\SimAsnDownloadHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;

class ArchiveSyncController extends Controller
{
    public function index(): View
    {
        return view('sim-asn.archive-sync.index', [
            'stats' => $this->getStats(),
            'queueSize' => $this->getQueueSize(),
            'perJenis' => $this->getPerJenisStats(),
            'recentErrors' => $this->getRecentErrors(),
        ]);
    }

    public function stats(): array
    {
        return [
            'stats' => $this->getStats(),
            'queueSize' => $this->getQueueSize(),
            'perJenis' => $this->getPerJenisStats(),
        ];
    }

    private function getStats(): array
    {
        $cacheKey = 'sim_asn_archive_stats';

        return Cache::remember($cacheKey, 10, function () {
            $total = SimAsnDownloadHistory::count();
            $success = SimAsnDownloadHistory::successful()->count();
            $failed = SimAsnDownloadHistory::failed()->count();
            $pending = SimAsnDownloadHistory::pending()->count();

            $uniqueNips = SimAsnDownloadHistory::distinct('nip')->count('nip');

            $lastRecord = SimAsnDownloadHistory::latest('updated_at')->first();

            return [
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'pending' => $pending,
                'unique_nips' => $uniqueNips,
                'last_updated' => $lastRecord?->updated_at?->toIso8601String(),
                'success_rate' => $total > 0 ? round(($success / $total) * 100, 1) : 0,
            ];
        });
    }

    private function getQueueSize(): int
    {
        try {
            $size = (int) Redis::llen('queues:sim-asn-sync');
            $reserved = (int) Redis::zcard('queues:sim-asn-sync:reserved');

            return $size + max(0, $reserved);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function getPerJenisStats(): array
    {
        $cacheKey = 'sim_asn_per_jenis_stats';

        return Cache::remember($cacheKey, 10, function () {
            return SimAsnDownloadHistory::selectRaw(
                'jenis_dokumen,
                 COUNT(*) as total,
                 SUM(status = "success") as success,
                 SUM(status = "failed") as failed,
                 SUM(status = "pending") as pending'
            )
                ->groupBy('jenis_dokumen')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => [
                    'jenis_dokumen' => $row->jenis_dokumen,
                    'total' => (int) $row->total,
                    'success' => (int) $row->success,
                    'failed' => (int) $row->failed,
                    'pending' => (int) $row->pending,
                    'rate' => $row->total > 0
                        ? round(($row->success / $row->total) * 100, 1)
                        : 0,
                ])
                ->toArray();
        });
    }

    private function getRecentErrors(int $limit = 10): array
    {
        return SimAsnDownloadHistory::failed()
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['nip', 'nama', 'jenis_dokumen', 'file_name', 'error_message', 'updated_at'])
            ->toArray();
    }
}
