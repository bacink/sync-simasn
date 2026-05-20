<?php

namespace App\Services\SimAsn;

use App\Helpers\SimAsnArchiveHelper;
use App\Models\SimAsnDownloadHistory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Encapsulates all download-and-save logic for SIM-ASN archived documents.
 *
 * Single responsibility: given document metadata + content, write the file
 * to storage and update (or create) the history record.  Network retrieval
 * is delegated to `download()` so tests can mock it independently.
 */
class SimAsnDownloadService
{
    private const USER_AGENT = 'KGB-PMK-ArchiveSync/2.0 (Laravel-Queue)';

    private const ERROR_LOG_PATH = 'logs/migration_errors.log';

    /**
     * Download content from URL with exponential-backoff retry.
     *
     * @return string|null Raw content on success, null on final failure.
     */
    public function download(string $url, int $attempt = 1, int $maxAttempts = 5): ?string
    {
        $content = $this->doDownload($url);

        if ($content !== false || $attempt >= $maxAttempts) {
            return $content === false ? null : $content;
        }

        $statusCode = $this->getLastStatusCode();

        if ($statusCode === 429) {
            $baseDelay = min(2 ** $attempt, 60);
            $jitter = random_int(0, 1000) / 1000;
            usleep((int) (($baseDelay + $jitter) * 1_000_000));

            return $this->download($url, $attempt + 1, $maxAttempts);
        }

        return null;
    }

    /**
     * Validate content based on file type using magic bytes.
     */
    public function isValidFile(string $content, string $fileType): bool
    {
        return match (strtolower($fileType)) {
            'pdf' => str_starts_with($content, '%PDF-'),
            'png' => str_starts_with($content, "\x89PNG"),
            'image' => str_starts_with($content, "\x89PNG") || str_starts_with($content, "\xFF\xD8\xFF"),
            default => str_starts_with($content, '%PDF-')
                || str_starts_with($content, "\x89PNG")
                || str_starts_with($content, "\xFF\xD8\xFF"),
        };
    }

    /**
     * Persist downloaded content to storage and update/create the history record.
     *
     * @param  SimAsnDownloadHistory  $record  Pre-created or fetched history record.
     * @param  string  $content  Raw file bytes.
     * @param  string  $fileType  e.g. 'pdf', 'png'.
     */
    public function saveFile(
        SimAsnDownloadHistory $record,
        string $content,
        string $fileType,
    ): void {
        $relativePath = $record->file_path;

        Storage::disk(SimAsnArchiveHelper::DISK)->put($relativePath, $content);

        $hash = hash('sha256', $content);
        $record->markSuccess($hash);

        Log::info('[ARCHIVE] Saved', [
            'nip' => $record->nip,
            'jenis' => $record->jenis_dokumen,
            'path' => $relativePath,
        ]);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private ?int $lastStatusCode = null;

    private function doDownload(string $url): string|false
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/pdf,image/*,application/octet-stream,*/*',
                    'X-Client-Id' => 'kgb-pmk-archive-sync',
                    'X-Request-Source' => 'sim-asn-archive-sync',
                ])
                ->get($url);

            $this->lastStatusCode = $response->status();

            if ($response->status() === 429) {
                return false;
            }

            if ($response->failed()) {
                return false;
            }

            return $response->body();
        } catch (RequestException) {
            return false;
        }
    }

    private function getLastStatusCode(): int
    {
        return $this->lastStatusCode ?? 0;
    }

    /**
     * Log an error to migration_errors.log (CSV format).
     * Log path: storage/logs/migration_errors.log (written outside the local disk root).
     */
    private function logError(string $nip, string $jenis, string $url, string $code, string $message): void
    {
        $timestamp = now()->toIso8601String();
        $line = "{$timestamp},{$nip},{$jenis},{$url},{$code},{$message}";
        $logFile = storage_path(self::ERROR_LOG_PATH);

        $dir = dirname($logFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($logFile, $line.PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
