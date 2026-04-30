<?php

namespace App\Console\Commands;

use App\Services\SimAsn\ArchiveDownloaderService;
use App\Services\SimAsn\SimAsnService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class SimAsnArchiveSync extends Command
{
    protected $signature = 'sim-asn:archive-sync
                            {--jenis= : Filter by employee type (pns|pppk|pppk_pw)}
                            {--limit=0 : Max employees to process (0 = all)}
                            {--ID= : Sync a single employee by SIM-ASN pegawai ID}
                            {--reset : Clear checkpoint and restart from page 1}
                            {--dry-run : List files without downloading}';

    protected $description = 'Migrate and sync archive documents from SIM-ASN API for all employees';

    /** Minimum jitter delay between batches (milliseconds) */
    private const JITTER_BASE_MS = 200;

    /** Maximum jitter delay between batches (milliseconds) */
    private const JITTER_MAX_MS = 800;

    public function handle(SimAsnService $simAsn): int
    {
        $jenis      = (string) $this->option('jenis') ?: '';
        $limit      = (int) $this->option('limit');
        $pegawaiId  = (string) $this->option('ID') ?: '';
        $reset      = (bool) $this->option('reset');
        $dryRun     = (bool) $this->option('dry-run');

        // --- Auth context (PHP 8.4 readonly-safe check) ---
        $userId = Auth::check() ? Auth::id() : null;
        $this->info('Running as user_id: ' . ($userId ?? 'CLI/scheduler'));

        if ($dryRun) {
            $this->warn('[DRY RUN] No files will be downloaded.');
        }

        // --- Single employee mode ---
        if ($pegawaiId !== '') {
            return $this->syncSingle($simAsn, $pegawaiId, $dryRun);
        }

        // --- Reset checkpoint ---
        if ($reset) {
            ArchiveDownloaderService::clearCheckpoint();
            $this->warn('Checkpoint cleared. Starting from page 1.');
        }

        // --- Load checkpoint ---
        $checkpoint = ArchiveDownloaderService::loadCheckpoint();
        $startPage   = 1;
        $cpProcessed = 0; // cumulative employees from checkpoint (informational only)

        if ($checkpoint && !$reset) {
            $cpJenis = $checkpoint['jenis'] ?? '';

            if ($cpJenis === $jenis) {
                $startPage   = (int) ($checkpoint['page'] ?? 1);
                $cpProcessed = (int) ($checkpoint['processed'] ?? 0);
                $this->info("Checkpoint: page={$startPage}, already_processed={$cpProcessed}. Continuing from page {$startPage}.");
            }
            else {
                $this->warn("Checkpoint jenis mismatch ('{$cpJenis}' vs '{$jenis}'). Starting from page 1.");
                $startPage = 1;
            }
        }

        // --- Bootstrap: get pagination metadata ---
        // Use --limit as page size when it's smaller than default 100
        $effectivePageSize = ($limit > 0) ? min(100, $limit) : 100;
        try {
            $paginator = $simAsn->listPegawaiPaginator($jenis ?: null, $effectivePageSize, 1);
            $totalEmployees = $paginator->total();
            $totalPages     = $paginator->lastPage();
        }
        catch (\Exception $e) {
            $this->error('Failed to connect to SIM-ASN API: ' . $e->getMessage());
            return 1;
        }

        if ($totalEmployees === 0) {
            $this->warn('No employees found.');
            return 0;
        }

        // Cap this run to --limit (0 = no cap = all remaining employees)
        // On resume, $startPage already points to the next unprocessed page
        // remaining = employees to process THIS run (starts from checkpoint page, capped by --limit)
        if ($limit > 0) {
            // --limit specified: run up to $limit employees from current page onward
            $effectiveLimit = $limit;
            $remaining       = max(0, $limit - $cpProcessed);
        }
        else {
            // --limit=0 (default): process all remaining employees
            $effectiveLimit = max(1, $totalEmployees - $cpProcessed);
            $remaining       = $effectiveLimit;
        }
        $startPage = max(1, $startPage);

        $this->info("Total employees: {$totalEmployees} | Server pages: {$totalPages}");
        $this->info("Limit: " . ($limit ? $limit : 'ALL') . " | Page size: {$effectivePageSize}");
        if ($cpProcessed > 0) {
            $this->info("Already processed: {$cpProcessed} | Remaining this run: {$remaining} (page {$startPage} onward)");
        }

        $progressBar = $this->output->createProgressBar($effectiveLimit);
        $progressBar->start();

        $downloader = new ArchiveDownloaderService($simAsn);
        $currentPage = $startPage;
        $thisRunDone = 0; // employees processed in this run

        while ($remaining > 0 && $currentPage <= $totalPages) {
            try {
                $paginator = $simAsn->listPegawaiPaginator($jenis ?: null, $effectivePageSize, $currentPage);
                $employees  = array_map(fn($item) => $item->toArray(), $paginator->items());
            }
            catch (\Exception $e) {
                $this->newLine();
                $this->error("Error fetching page {$currentPage}: {$e->getMessage()}");
                ArchiveDownloaderService::saveCheckpoint($currentPage, $cpProcessed + $thisRunDone, $jenis);
                return 1;
            }

            $downloader->syncPage($employees, $dryRun);

            $thisRunDone += count($employees);
            $remaining   -= count($employees);
            $progressBar->advance(count($employees));

            // Save checkpoint: next run starts from this page
            ArchiveDownloaderService::saveCheckpoint($currentPage, $cpProcessed + $thisRunDone, $jenis);

            // Random jitter between batches to avoid WAF detection

            // Recalculate remaining for next iteration
            $remaining = max(0, $effectiveLimit - $thisRunDone);

            // Random jitter between batches to avoid WAF detection
            $jitterMs = random_int(self::JITTER_BASE_MS, self::JITTER_MAX_MS);
            usleep($jitterMs * 1000);

            // Release memory
            unset($employees, $paginator);

            $currentPage++;
        }

        $progressBar->finish();
        $this->newLine(2);

        $stats = $downloader->getStats();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Employees processed',   $cpProcessed + $thisRunDone],
                ['Files downloaded',     $stats['downloaded']],
                ['Files skipped (exists)', $stats['skipped']],
                ['Errors',               $stats['errors']],
            ]
        );

        if ($stats['errors'] > 0) {
            $logPath = storage_path('logs/migration_errors.log');
            $this->warn("{$stats['errors']} error(s) occurred. See: {$logPath}");
        }

        if (!$dryRun) {
            $this->info('Checkpoint saved. Run again to resume if needed.');
        }

        return 0;
    }

    /**
     * Sync documents for a single employee by their SIM-ASN pegawai ID.
     */
    private function syncSingle(SimAsnService $simAsn, string $pegawaiId, bool $dryRun): int
    {
        $this->info("Syncing single employee: {$pegawaiId}");

        // Fetch employee detail to get NIP
        try {
            $employee = $simAsn->getPegawai($pegawaiId);
        }
        catch (\Exception $e) {
            $this->error('Failed to fetch employee: ' . $e->getMessage());
            return 1;
        }

        if (empty($employee)) {
            $this->error("Employee not found: {$pegawaiId}");
            return 1;
        }

        $nip = $employee['nip'] ?? $pegawaiId;
        $this->info("NIP: {$nip} | Name: " . ($employee['nama'] ?? 'N/A'));

        if ($dryRun) {
            $this->warn('[DRY RUN] No files will be downloaded.');
        }

        $downloader = new ArchiveDownloaderService($simAsn);
        $downloader->syncEmployee($nip, $pegawaiId, $dryRun);

        $stats = $downloader->getStats();
        $this->info("Downloaded: {$stats['downloaded']} | Skipped: {$stats['skipped']} | Errors: {$stats['errors']}");

        return $stats['errors'] > 0 ? 1 : 0;
    }
}
