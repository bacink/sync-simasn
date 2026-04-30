<?php

namespace App\Services\SimAsn;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArchiveDownloaderService
{
    private const CHECKPOINT_KEY = 'sim_asn_archive_checkpoint';

    private const ERROR_LOG_PATH = 'logs/migration_errors.log';

    /** User-Agent string used in all HTTP requests */
    private const USER_AGENT = 'KGB-PMK-ArchiveSync/1.0 (Laravel/CLI)';

    /** @var array{downloaded:int,skipped:int,errors:int} */
    private array $stats = [
        'downloaded' => 0,
        'skipped'    => 0,
        'errors'     => 0,
    ];

    public function __construct(
        protected SimAsnService $simAsn,
        protected string $disk = '',
        protected string $basePath = 'arsip',
    ) {
        $this->disk = $this->disk ?: config('filesystems.default', 'local');
    }

    /**
     * Sync documents & files for a page of employees.
     *
     * @param array $employees   Paginated employee array from SimAsnService
     * @param bool  $dryRun      If true, log what would be downloaded without saving
     * @return array             Stats snapshot
     */
    public function syncPage(array $employees, bool $dryRun = false): array
    {
        foreach ($employees as $employee) {
            $nip = $employee['nip'] ?? null;

            if (!$nip) {
                $this->logError('-', '-', '-', 'missing_nip', 'Employee record missing NIP field');
                $this->stats['errors']++;
                continue;
            }

            $this->syncEmployee($nip, $employee['id'] ?? null, $dryRun);
        }

        return $this->stats;
    }

    /**
     * Sync all dokumen for a single employee.
     */
    public function syncEmployee(string $nip, ?string $pegawaiId, bool $dryRun = false): void
    {
        if (!$pegawaiId) {
            $this->logError($nip, '-', '-', 'missing_pegawai_id', 'No SIM-ASN employee ID available');
            $this->stats['errors']++;
            return;
        }

        // dokumen endpoint — returns array of {id, jenis, label, nomor, tanggal, file: {url, name, file_type} | null}
        $dokumen = $this->safeGet(fn() => $this->simAsn->getDokumen($pegawaiId), $nip, 'dokumen');

        if (empty($dokumen)) {
            return;
        }

        foreach ($dokumen as $doc) {
            $this->syncDocument($nip, $doc, $dryRun);
        }
    }

    /**
     * Sync a single document from the dokumen API response.
     *
     * Expected structure:
     * {
     *   "id": 2319,
     *   "jenis": "ijazah",
     *   "label": "Ijazah Sekolah Dasar",
     *   "nomor": "03OA oa 0449261",
     *   "tanggal": "1992-06-01",
     *   "lembaga_penerbit": "SDN Panulisan...",
     *   "jabatan_penandatangan": "Kepala Sekolah",
     *   "pejabat_penandatangan": "Kasda Holil",
     *   "file": {
     *     "id": 992093,
     *     "id_pegawai": "c0e6083c-...",
     *     "url": "https://api.sim-asn.../file/view/...",
     *     "file_type": "pdf",
     *     "name": "Ijazah Sekolah Dasar"
     *   } | null
     * }
     */
    public function syncDocument(string $nip, array $doc, bool $dryRun = false): void
    {
        $jenis = $doc['jenis'] ?? 'UNKNOWN';
        $label = $doc['label']  ?? $jenis;

        // file is nested inside doc, may be null
        $file = $doc['file'] ?? null;

        if (!$file || !($file['url'] ?? null)) {
            $this->logError($nip, $jenis, 'null', 'missing_file', "Document '{$label}' has no attached file");
            $this->stats['skipped']++;
            return;
        }

        $url      = $file['url'];
        $fileName = $file['name'] ?? $label;
        $fileType = $file['file_type'] ?? 'pdf';

        // Only download PDF files, skip everything else
        if (strtolower($fileType) !== 'pdf') {
            $this->stats['skipped']++;
            return;
        }

        // Determine actual extension from file_type or URL
        $extension = $this->resolveExtension($fileType, $url);

        // Generate target filename
        $baseFilename = ArchiveNamingMapper::generate($nip, $jenis, $label);
        // Replace .pdf placeholder with actual extension
        $filename     = preg_replace('/\.pdf$/i', ".{$extension}", $baseFilename, 1);

        $relativePath = "{$this->basePath}/{$nip}/{$filename}";

        // Check if already exists (skip download)
        if (Storage::disk($this->disk)->exists($relativePath)) {
            $this->stats['skipped']++;
            return;
        }

        if ($dryRun) {
            Log::info("[DRY-RUN] Would download: {$url} → {$relativePath}");
            return;
        }

        // Download and validate
        $content = $this->download($url);

        if ($content === null) {
            $this->logError($nip, $jenis, $url, 'download_failed', "Failed to download document '{$fileName}'");
            $this->stats['errors']++;
            return;
        }

        // PDF/image integrity check
        if (!$this->isValidFile($content, $fileType)) {
            $this->logError($nip, $jenis, $url, 'invalid_file_header', "Downloaded file is not a valid {$fileType} for '{$fileName}'");
            $this->stats['errors']++;
            return;
        }

        // Save to storage
        Storage::disk($this->disk)->put($relativePath, $content);
        $this->stats['downloaded']++;

        Log::info("[ARCHIVE] Saved: {$relativePath}");
    }

    /**
     * Download file content from URL with retry + exponential backoff.
     * Returns null on final failure.
     *
     * @param string $url
     * @return string|null
     */
    public function download(string $url, int $attempt = 1, int $maxAttempts = 5): ?string
    {
        $content = $this->doDownload($url);

        // If succeeded or final attempt, return
        if ($content !== false || $attempt >= $maxAttempts) {
            return $content === false ? null : $content;
        }

        $statusCode = $this->getLastStatusCode();

        // Rate-limited (429) — exponential backoff
        if ($statusCode === 429) {
            // Exponential backoff: 2^attempt seconds + random jitter (0–1s)
            $baseDelay = min(2 ** $attempt, 60); // cap at 60 seconds
            $jitter     = random_int(0, 1000) / 1000; // 0.0 – 1.0 seconds
            $delayMs    = (int) (($baseDelay + $jitter) * 1000);

            usleep($delayMs * 1000);

            return $this->download($url, $attempt + 1, $maxAttempts);
        }

        // Other error — return null (no retry)
        return null;
    }

    /**
     * Perform the actual HTTP GET request.
     *
     * @return string|false  String on success, false on failure
     */
    private function doDownload(string $url): string|false
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent'       => self::USER_AGENT,
                    'Accept'           => 'application/pdf,image/*,application/octet-stream,*/*',
                    'X-Client-Id'      => 'kgb-pmk-archive-sync',
                    'X-Request-Source' => 'sim-asn-archive-sync',
                ])
                ->get($url);

            // Track status code for retry logic
            $this->lastStatusCode = $response->status();

            if ($response->status() === 429) {
                return false;
            }

            if ($response->failed()) {
                return false;
            }

            return $response->body();
        } catch (\Throwable) {
            return false;
        }
    }

    /** @var int|null */
    private ?int $lastStatusCode = null;

    private function getLastStatusCode(): int
    {
        return $this->lastStatusCode ?? 0;
    }

    /**
     * Validate file content based on expected file_type.
     *
     * - PDF magic bytes: %PDF-  (0x25 0x50 0x44 0x46 0x2D)
     * - PNG magic bytes: \x89PNG (0x89 0x50 0x4E 0x47)
     * - JPEG magic bytes: \xFF\xD8\xFF
     */
    public function isValidFile(string $content, string $fileType): bool
    {
        return match (strtolower($fileType)) {
            'pdf'    => str_starts_with($content, "%PDF-"),
            'png'    => str_starts_with($content, "\x89PNG"),
            'image'  => str_starts_with($content, "\x89PNG") || str_starts_with($content, "\xFF\xD8\xFF"),
            default  => str_starts_with($content, "%PDF-") || str_starts_with($content, "\x89PNG") || str_starts_with($content, "\xFF\xD8\xFF"),
        };
    }

    /**
     * Resolve file extension from file_type or URL.
     */
    private function resolveExtension(string $fileType, string $url): string
    {
        return match (strtolower($fileType)) {
            'pdf'   => 'pdf',
            'png'   => 'png',
            'image' => $this->inferImageExtension($url),
            default => 'pdf',
        };
    }

    /**
     * Infer image extension from URL if possible.
     */
    private function inferImageExtension(string $url): string
    {
        if (preg_match('/\.(\w+)(?:\?|$)/', $url, $m)) {
            $ext = strtolower($m[1]);
            return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $ext : 'jpg';
        }
        return 'jpg';
    }

    /**
     * Safely call a SIM-ASN service method, returning [] on failure.
     */
    private function safeGet(callable $fn, string $nip, string $context): array
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            $this->logError($nip, $context, '-', 'api_error', "Failed to fetch {$context}: {$e->getMessage()}");
            $this->stats['errors']++;
            return [];
        }
    }

    /**
     * Log an error to migration_errors.log (CSV format).
     * Log path: storage/logs/migration_errors.log (written outside the local disk root).
     */
    private function logError(string $nip, string $jenis, string $url, string $code, string $message): void
    {
        $timestamp = now()->toIso8601String();
        $line      = "{$timestamp},{$nip},{$jenis},{$url},{$code},{$message}";
        $logFile   = storage_path(self::ERROR_LOG_PATH);

        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get current stats snapshot.
     *
     * @return array{downloaded:int,skipped:int,errors:int}
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Reset stats counter.
     */
    public function resetStats(): void
    {
        $this->stats = ['downloaded' => 0, 'skipped' => 0, 'errors' => 0];
    }

    // -----------------------------------------------------------------
    // Checkpoint helpers
    // -----------------------------------------------------------------

    public static function saveCheckpoint(int $page, int $processed, string $jenis = ''): void
    {
        Cache::put(self::CHECKPOINT_KEY, [
            'page'      => $page,
            'processed' => $processed,
            'jenis'     => $jenis,
            'saved_at'  => now()->toIso8601String(),
        ], now()->addDays(7));
    }

    public static function loadCheckpoint(): ?array
    {
        return Cache::get(self::CHECKPOINT_KEY);
    }

    public static function clearCheckpoint(): void
    {
        Cache::forget(self::CHECKPOINT_KEY);
    }
}
