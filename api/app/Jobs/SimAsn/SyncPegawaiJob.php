<?php

namespace App\Jobs\SimAsn;

use App\Services\SimAsn\SimAsnService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Fetches the document list for a single employee and dispatches one
 * SyncDokumenJob per document that passes the --dokumen filter.
 */
class SyncPegawaiJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use Queueable;

    /**
     * Job timeout in seconds — must be less than retry_after in config/queue.php.
     */
    public $timeout = 60;

    /**
     * Max retry attempts for transient failures.
     */
    public $tries = 3;

    /**
     * Exponential backoff in seconds between attempts.
     */
    public $backoff = [3, 10, 30];

    public function __construct(
        public readonly string $nip,
        public readonly string $pegawaiId,
        public readonly ?string $nama = null,
        public readonly array $dokumenFilter = [], // empty = all types
    ) {
        $this->onQueue('sim-asn-sync');
    }

    public function handle(SimAsnService $simAsn): void
    {
        $docs = $this->safeGet(
            fn () => $simAsn->getDokumen($this->pegawaiId),
            'getDokumen',
        );

        if (empty($docs)) {
            Log::debug('[SyncPegawai] No documents', ['nip' => $this->nip]);

            return;
        }

        foreach ($docs as $doc) {
            $jenis = $doc['jenis'] ?? 'unknown';

            // Apply --dokumen filter: skip if a filter list exists and this jenis doesn't match
            if ($this->dokumenFilter && ! in_array($jenis, $this->dokumenFilter, true)) {
                continue;
            }

            SyncDokumenJob::dispatch(
                nip: $this->nip,
                nama: $this->nama,
                doc: $doc,
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[SyncPegawai] Job failed permanently', [
            'nip' => $this->nip,
            'pegawaiId' => $this->pegawaiId,
            'error' => $exception->getMessage(),
        ]);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function safeGet(callable $fn, string $context): array
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Log::error('[SyncPegawai] API error', [
                'nip' => $this->nip,
                'context' => $context,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
