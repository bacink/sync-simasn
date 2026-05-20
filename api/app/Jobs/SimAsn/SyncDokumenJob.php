<?php

namespace App\Jobs\SimAsn;

use App\Helpers\Option;
use App\Helpers\SimAsnArchiveHelper;
use App\Models\SimAsnDownloadHistory;
use App\Services\SimAsn\ArchiveNamingMapper;
use App\Services\SimAsn\SimAsnDownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Atomic job — downloads and saves a single archive document.
 *
 * Lifecycle:
 *   1. Resolve or create the history record (unique on nip+jenis_dokumen+file_name).
 *   2. Skip if status = success.
 *   3. Retry if status = failed (reset to pending).
 *   4. Download + validate + save file.
 *   5. Update record status.
 */
class SyncDokumenJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public $timeout = 60;

    public $tries = 3;

    public $backoff = [5, 15, 30];

    /** Unique job lock — same document cannot be queued twice simultaneously. */
    public $uniqueFor = 3600;

    public function __construct(
        public readonly string $nip,
        public readonly ?string $nama,
        /** @param array{jenis, label, file: {url, name, file_type} | null} */
        public readonly array $doc,
    ) {
        $this->onQueue('sim-asn-sync');
    }

    public function handle(SimAsnDownloadService $downloader): void
    {
        $this->resolveRecord()->ifSome(
            fn (Model $record) => $this->process($record, $downloader),
        );
    }

    public function failed(\Throwable $exception): void
    {
        $this->resolveRecord()->ifSome(
            fn (Model $record) => $record->markFailed($exception->getMessage()),
        );

        Log::error('[SyncDokumen] Job failed permanently', [
            'nip' => $this->nip,
            'jenis' => $this->doc['jenis'] ?? 'unknown',
            'file' => $this->doc['file']['name'] ?? '?',
            'error' => $exception->getMessage(),
        ]);
    }

    // ─── Public helpers (used by tests) ─────────────────────────────────────

    public function uniqueId(): string
    {
        $file = $this->doc['file'] ?? [];
        $url = $file['url'] ?? '';

        return Str::slug("{$this->nip}-{$this->doc['jenis']}-{$url}");
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Resolve or create the history record.
     * Returns an Option-like: either a record was found/created, or none.
     */
    private function resolveRecord(): Option
    {
        $jenis = $this->doc['jenis'] ?? 'unknown';
        $label = $this->doc['label'] ?? $jenis;
        $file = $this->doc['file'] ?? null;

        if (! $file || ! ($file['url'] ?? null)) {
            return Option::none();
        }

        $fileName = $this->generateFileName($file, $label);
        $filePath = SimAsnArchiveHelper::storagePath($jenis, $fileName);

        /** @var SimAsnDownloadHistory|null $existing */
        $existing = SimAsnDownloadHistory::where('nip', $this->nip)
            ->where('jenis_dokumen', $jenis)
            ->where('file_name', $fileName)
            ->first();

        if ($existing) {
            if ($existing->status === 'success') {
                return Option::none(); // already done — skip silently
            }
            if ($existing->status === 'failed') {
                $existing->resetForRetry();
            }

            // else 'pending' → continue
            return Option::some($existing);
        }

        $record = SimAsnDownloadHistory::create([
            'nip' => $this->nip,
            'nama' => $this->nama,
            'jenis_dokumen' => $jenis,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => 'pending',
            'attempt_count' => 0,
        ]);

        return Option::some($record);
    }

    /**
     * Download, validate, and persist the file.
     */
    private function process(Model $record, SimAsnDownloadService $downloader): void
    {
        $file = $this->doc['file'];
        $url = $file['url'];
        $fileType = $file['file_type'] ?? 'pdf';

        // Only download PDFs
        if (strtolower($fileType) !== 'pdf') {
            $record->markFailed("Unsupported file type: {$fileType}");

            return;
        }

        $content = $downloader->download($url);

        if ($content === null) {
            $record->markFailed("Download failed for: {$url}");

            return;
        }

        if (! $downloader->isValidFile($content, $fileType)) {
            $record->markFailed("Invalid file magic bytes for: {$file['name']}");

            return;
        }

        $downloader->saveFile($record, $content, $fileType);
    }

    private function generateFileName(array $file, string $label): string
    {
        $jenis = $this->doc['jenis'] ?? 'unknown';
        $extension = SimAsnArchiveHelper::resolveExtension(
            $file['file_type'] ?? 'pdf',
            $file['url'] ?? '',
        );

        $base = ArchiveNamingMapper::generate($this->nip, $jenis, $label);

        return preg_replace('/\.pdf$/i', ".{$extension}", $base, 1)
            ?: "{$this->nip}_{$jenis}.{$extension}";
    }
}
