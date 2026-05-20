<?php

namespace App\Console\Commands;

use App\Helpers\SimAsnArchiveHelper;
use App\Jobs\SimAsn\SyncPegawaiJob;
use App\Services\SimAsn\SimAsnService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrator — fetches employees and dispatches SyncPegawaiJob batches.
 *
 * No file I/O happens here.  Dispatched jobs are processed by queue workers.
 */
class SimAsnArchiveSync extends Command
{
    protected $signature = 'sim-asn:archive-sync
                            {--jenis= : Filter by employee type (pns|pppk|pppk_pw)}
                            {--limit=0 : Max employees to dispatch (0 = all)}
                            {--ID= : Sync a single employee by SIM-ASN pegawai ID}
                            {--dokumen= : Comma-separated dokumen jenis to sync (default: all)}
                            {--reset : Clear checkpoint and restart from page 1}
                            {--dry-run : List what would be dispatched without queueing jobs}';

    protected $description = 'Dispatch queue-based archive sync jobs for all employees';

    private const CHECKPOINT_KEY = 'sim_asn_archive_sync_checkpoint';

    public function handle(SimAsnService $simAsn): int
    {
        $jenis = (string) $this->option('jenis') ?: '';
        $limit = (int) $this->option('limit');
        $pegawaiId = (string) $this->option('ID') ?: '';
        $dokumenRaw = (string) $this->option('dokumen') ?: '';
        $reset = (bool) $this->option('reset');
        $dryRun = (bool) $this->option('dry-run');

        $dokumenFilter = $dokumenRaw
            ? array_filter(array_map('trim', explode(',', $dokumenRaw)))
            : [];

        if ($dryRun) {
            $this->warn('[DRY RUN] Jobs will be listed but not dispatched.');
        }

        // ── Single employee mode ──────────────────────────────────────────────
        if ($pegawaiId !== '') {
            return $this->dispatchSingle($simAsn, $pegawaiId, $dokumenFilter, $dryRun);
        }

        // ── Reset checkpoint ─────────────────────────────────────────────────
        if ($reset) {
            $this->clearCheckpoint();
            $this->warn('Checkpoint cleared. Starting from page 1.');
        }

        // ── Bootstrap: resolve page range from checkpoint + --limit ───────────
        $checkpoint = $this->loadCheckpoint();
        $startPage = 1;
        $cpProcessed = 0;

        if ($checkpoint && ! $reset && ($checkpoint['jenis'] ?? '') === $jenis) {
            $startPage = max(1, (int) ($checkpoint['page'] ?? 1));
            $cpProcessed = max(0, (int) ($checkpoint['processed'] ?? 0));
            $this->info("Resuming: page={$startPage}, processed={$cpProcessed}.");
        }

        $pageSize = SimAsnArchiveHelper::CHUNK_SIZE;

        try {
            $paginator = $simAsn->listPegawaiPaginator($jenis ?: null, $pageSize, 1);
            $totalEmployees = $paginator->total();
            $totalPages = $paginator->lastPage();
        } catch (\Exception $e) {
            $this->error('Failed to connect to SIM-ASN API: '.$e->getMessage());

            return 1;
        }

        if ($totalEmployees === 0) {
            $this->warn('No employees found.');

            return 0;
        }

        $remaining = $this->resolveRemaining($limit, $cpProcessed, $totalEmployees);

        $this->info("Total employees : {$totalEmployees}");
        $this->info("Total pages    : {$totalPages}");
        $this->info("Start page     : {$startPage}");
        $this->info('Limit          : '.($limit ? $limit : 'ALL'));
        $this->info('Dokumen filter : '.SimAsnArchiveHelper::formatJenisList($dokumenFilter));

        if ($cpProcessed > 0) {
            $this->info("Already dispatched: {$cpProcessed} | Remaining this run: {$remaining}");
        }

        // ── Chunk-and-dispatch loop ────────────────────────────────────────────
        $progressBar = $this->output->createProgressBar($remaining);
        $progressBar->start();

        $currentPage = $startPage;
        $thisRunDone = 0;

        while ($remaining > 0 && $currentPage <= $totalPages) {
            try {
                $paginator = $simAsn->listPegawaiPaginator($jenis ?: null, $pageSize, $currentPage);
                $employees = $paginator->items();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("API error on page {$currentPage}: {$e->getMessage()}");
                $this->saveCheckpoint($currentPage, $cpProcessed + $thisRunDone, $jenis);

                return 1;
            }

            $batch = [];
            $processedThisPage = 0;
            foreach ($employees as $employee) {
                if ($processedThisPage >= $remaining) {
                    break;
                }

                $arr = is_object($employee) && method_exists($employee, 'toArray')
                    ? $employee->toArray()
                    : (array) $employee;
                $nip = $arr['nip'] ?? null;

                if (! $nip) {
                    Log::warning('[SimAsnArchiveSync] Skipping employee without NIP', ['id' => $arr['id'] ?? '?']);

                    continue;
                }

                if ($dryRun) {
                    $this->line("  [DRY] Would dispatch: NIP={$nip}, ID={$arr['id']}");
                    $processedThisPage++;
                } else {
                    $batch[] = new SyncPegawaiJob(
                        nip: $nip,
                        pegawaiId: (string) ($arr['id'] ?? ''),
                        nama: $arr['nama'] ?? null,
                        dokumenFilter: $dokumenFilter,
                    );
                    $processedThisPage++;
                }
            }

            if ($batch) {
                Bus::batch($batch)
                    ->name("sim-asn-sync-page-{$currentPage}")
                    ->onQueue('sim-asn-sync')
                    ->dispatch();
            }

            $thisRunDone += $processedThisPage;
            $remaining = max(0, $remaining - $processedThisPage);
            $progressBar->advance($processedThisPage);
            $this->saveCheckpoint($currentPage, $cpProcessed + $thisRunDone, $jenis);

            $currentPage++;
        }

        $progressBar->finish();
        $this->newLine(2);

        $totalProcessed = $cpProcessed + $thisRunDone;

        if ($dryRun) {
            $this->info("Dry run complete. Would have dispatched {$thisRunDone} employee jobs.");
        } else {
            $this->info("Dispatched {$thisRunDone} jobs. Total dispatched so far: {$totalProcessed}.");
            $this->info('Workers: php artisan queue:work redis --queue=sim-asn-sync');
        }

        return 0;
    }

    // ─── Single-employee shortcut ─────────────────────────────────────────────

    private function dispatchSingle(
        SimAsnService $simAsn,
        string $pegawaiId,
        array $dokumenFilter,
        bool $dryRun,
    ): int {
        $this->info("Syncing single employee: {$pegawaiId}");

        try {
            $employee = $simAsn->getPegawai($pegawaiId);
        } catch (\Exception $e) {
            $this->error('Failed to fetch employee: '.$e->getMessage());

            return 1;
        }

        if (empty($employee)) {
            $this->error("Employee not found: {$pegawaiId}");

            return 1;
        }

        $nip = $employee['nip'] ?? $pegawaiId;
        $nama = $employee['nama'] ?? null;

        $this->info("NIP : {$nip}");
        $this->info('Nama: '.($nama ?? 'N/A'));
        $this->info('Dokumen filter: '.SimAsnArchiveHelper::formatJenisList($dokumenFilter));

        if ($dryRun) {
            $this->warn('[DRY RUN] No job dispatched.');

            return 0;
        }

        SyncPegawaiJob::dispatch(
            nip: $nip,
            pegawaiId: $pegawaiId,
            nama: $nama,
            dokumenFilter: $dokumenFilter,
        )->onQueue('sim-asn-sync');

        $this->info('Job dispatched to queue: sim-asn-sync');

        return 0;
    }

    // ─── Checkpoint helpers ────────────────────────────────────────────────────

    private function saveCheckpoint(int $page, int $processed, string $jenis): void
    {
        cache()->put(self::CHECKPOINT_KEY, [
            'page' => $page,
            'processed' => $processed,
            'jenis' => $jenis,
            'saved_at' => now()->toIso8601String(),
        ], now()->addDays(7));
    }

    private function loadCheckpoint(): ?array
    {
        return cache()->get(self::CHECKPOINT_KEY);
    }

    private function clearCheckpoint(): void
    {
        cache()->forget(self::CHECKPOINT_KEY);
    }

    // ─── Private helpers ───────────────────────────────────────────────────────

    private function resolveRemaining(int $limit, int $cpProcessed, int $total): int
    {
        if ($limit > 0) {
            return max(0, $limit - $cpProcessed);
        }

        return max(0, $total - $cpProcessed);
    }
}
