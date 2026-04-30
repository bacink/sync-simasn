<?php

namespace App\Console\Commands;

use App\Services\SimAsn\SimAsnService;
use Illuminate\Console\Command;

class SimAsnIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sim-asn:integrate
                            {action : Action to perform (test|search|list|detail|golongan|jabatan|sync)}
                            {query? : Search query or employee ID depending on action}
                            {--limit=100 : Limit results}
                            {--jenis= : Filter by employee type (pns|pppk|pppk_pw)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Integrate with SIM-ASN API: test, search, detail, golongan, jabatan, and sync employee data';

    /**
     * Execute the console command.
     */
    public function handle(SimAsnService $service): int
    {
        $action = $this->argument('action');
        $query  = $this->argument('query');

        return match ($action) {
            'test'      => $this->actionTest($service),
            'search'    => $this->actionSearch($service, $query),
            'list'      => $this->actionList($service),
            'detail'    => $this->actionDetail($service, $query),
            'golongan'  => $this->actionGolongan($service, $query),
            'jabatan'   => $this->actionJabatan($service, $query),
            'sync'      => $this->actionSync($service, $query),
            default     => $this->invalidAction(),
        };
    }

    // -----------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------

    private function actionTest(SimAsnService $service): int
    {
        $this->info('Testing SIM-ASN connection...');

        try {
            $results = $service->searchPegawai('test', null, 100);
            $count   = count($results);

            if ($count > 0) {
                $this->info("✓ Connection successful. Retrieved {$count} sample records.");
                $this->printPegawaiTable([$results[0]]);
                return 0;
            }

            $this->warn('Connection OK but returned no records.');
            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Connection failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function actionSearch(SimAsnService $service, ?string $query): int
    {
        $query ??= $this->ask('Enter search keyword (name, NIP, or partial text)');
        $limit = (int) $this->option('limit');
        $jenis = $this->option('jenis');

        $this->info("Searching for: {$query}" . ($jenis ? " [jenis={$jenis}]" : ''));

        try {
            $results = $service->searchPegawai($query, $jenis, $limit);
            $count   = count($results);

            if ($count === 0) {
                $this->warn('No results found.');
                return 0;
            }

            $this->info("Found {$count} record(s). Showing up to {$limit}.");
            $this->printPegawaiTable(array_slice($results, 0, $limit));

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function actionList(SimAsnService $service): int
    {
        $limit = (int) $this->option('limit');
        $jenis = $this->option('jenis');

        $jenisLabel = $jenis ? " [jenis={$jenis}]" : '';
        $this->info("Listing all employees (limit: {$limit}){$jenisLabel}...");

        try {
            $results = $service->listPegawai($jenis, $limit);

            if (empty($results)) {
                $this->warn('No employees found.');
                return 0;
            }

            $total   = count($results);
            $display = array_slice($results, 0, $limit);

            $this->info("Total records: {$total}. Showing up to {$limit}.");
            $this->printPegawaiTable($display);

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function actionDetail(SimAsnService $service, ?string $query): int
    {
        $query ??= $this->ask('Enter employee ID or NIP');

        $this->info("Fetching detail for ID: {$query}");

        try {
            $pegawai = $service->getPegawai($query);

            if (empty($pegawai)) {
                $this->warn('Employee not found.');
                return 0;
            }

            $this->printDetail($pegawai);

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function actionGolongan(SimAsnService $service, ?string $query): int
    {
        $query ??= $this->ask('Enter employee ID or NIP');

        $this->info("Fetching golongan (rank) history for: {$query}");

        try {
            $riwayat = $service->getRiwayatGolongan($query);
            $last    = $service->getLastGolongan($query);

            if (empty($riwayat)) {
                $this->warn('No golongan history found.');
                return 0;
            }

            $this->info('All golongan records:');
            $this->printGolonganTable($riwayat);

            if ($last) {
                $this->newLine();
                $this->info('Latest golongan:');
                $this->printDetail($last);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function actionJabatan(SimAsnService $service, ?string $query): int
    {
        $query ??= $this->ask('Enter employee ID or NIP');

        $this->info("Fetching jabatan (position) history for: {$query}");

        try {
            $riwayat = $service->getRiwayatJabatan($query);

            if (empty($riwayat)) {
                $this->warn('No jabatan history found.');
                return 0;
            }

            $this->printJabatanTable($riwayat);

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function actionSync(SimAsnService $service, ?string $query): int
    {
        $query ??= $this->ask('Enter search keyword to sync (e.g., employee name or NIP)');
        $limit  = (int) $this->option('limit');
        $jenis  = $this->option('jenis');

        $this->info("Syncing employee data for keyword: {$query}" . ($jenis ? " [jenis={$jenis}]" : '') . " (limit: {$limit})");

        $bar = $this->output->createProgressBar($limit);
        $bar->start();

        try {
            $results = $service->searchPegawai($query, $jenis, $limit);
            $synced  = [];

            foreach ($results as $pegawai) {
                $id       = $pegawai['id'] ?? null;
                $nip      = $pegawai['nip'] ?? '-';
                $nama     = $pegawai['nama'] ?? '-';
                $golongan = $this->extractField($pegawai['golongan'] ?? null, 'nama');
                $lastGol  = $id ? $service->getLastGolongan($id) : null;
                $lastJab  = $id ? $service->getRiwayatJabatan($id) : [];

                $synced[] = [
                    'nip'          => $nip,
                    'nama'         => $nama,
                    'golongan'     => $golongan,
                    'golongan_terakhir' => $this->extractField($lastGol, 'nama'),
                    'jabatan_count' => count($lastJab),
                    'id_sim_asn'   => $id,
                ];

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("Synced " . count($synced) . " employee(s).");
            $this->printSyncTable($synced);

            return 0;
        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine();
            $this->error('Sync error: ' . $e->getMessage());
            return 1;
        }
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function invalidAction(): int
    {
        $this->error("Invalid action. Available actions:");
        $this->line('  test      — Test SIM-ASN connection');
        $this->line('  search    — Search employees by keyword');
        $this->line('  list      — List all employees (default limit 100)');
        $this->line('  detail    — Get full employee detail by ID/NIP');
        $this->line('  golongan  — Get golongan (rank) history');
        $this->line('  jabatan   — Get jabatan (position) history');
        $this->line('  sync      — Sync employee data with full profile');
        $this->newLine();
        $this->line('Usage examples:');
        $this->line('  php artisan sim-asn:integrate test');
        $this->line('  php artisan sim-asn:integrate list');
        $this->line('  php artisan sim-asn:integrate list --jenis=pns');
        $this->line('  php artisan sim-asn:integrate list --jenis=pppk');
        $this->line('  php artisan sim-asn:integrate search "Budi"');
        $this->line('  php artisan sim-asn:integrate detail 12345');
        $this->line('  php artisan sim-asn:integrate golongan 12345');
        $this->line('  php artisan sim-asn:integrate sync  "Budi" --limit=10 --jenis=pppk');

        return 1;
    }

    private function extractField(mixed $value, string $field): string
    {
        if (is_array($value)) {
            return $value[$field] ?? '-';
        }
        if (is_object($value)) {
            return $value->{$field} ?? '-';
        }
        return (string) ($value ?? '-');
    }

    private function printPegawaiTable(array $items): void
    {
        $headers = ['ID', 'NIP', 'Nama', 'Golongan', 'Status'];

        $rows     = array_map(fn($p) => [
            substr($p['id'] ?? '-', 0, 12),
            $p['nip'] ?? '-',
            $p['nama'] ?? '-',
            $this->extractField($p['golongan'] ?? null, 'nama'),
            $p['status_pegawai'] ?? $p['jenis'] ?? '-',
        ], $items);

        $this->newLine();
        $this->table($headers, $rows);
    }

    private function printDetail(array $item): void
    {
        $this->newLine();
        $this->table(['Field', 'Value'], collect($item)->map(fn($v, $k) => [$k, is_array($v) ? json_encode($v) : $v])->all());
    }

    private function printGolonganTable(array $items): void
    {
        $headers = ['No', 'Nama Golongan', 'TMT', 'Jenis', 'Sumber'];
        $rows    = array_map(fn($r, $i) => [
            $i + 1,
            $r['nama'] ?? '-',
            $r['tmt'] ?? '-',
            $r['jenis'] ?? '-',
            $r['sumber'] ?? '-',
        ], array_values($items));

        $this->newLine();
        $this->table($headers, $rows);
    }

    private function printJabatanTable(array $items): void
    {
        $headers = ['No', 'Nama Jabatan', 'TMT', 'Tipe', 'Eselon'];
        $rows    = array_map(fn($r, $i) => [
            $i + 1,
            $r['nama'] ?? '-',
            $r['tmt'] ?? '-',
            $r['tipe'] ?? '-',
            $r['eselon'] ?? '-',
        ], array_values($items));

        $this->newLine();
        $this->table($headers, $rows);
    }

    private function printSyncTable(array $items): void
    {
        $headers = ['NIP', 'Nama', 'Golongan', 'Gol. Terakhir', 'Jabatan#', 'ID SIM-ASN'];
        $rows    = array_map(fn($s) => [
            $s['nip'],
            $s['nama'],
            $s['golongan'],
            $s['golongan_terakhir'],
            $s['jabatan_count'],
            substr($s['id_sim_asn'] ?? '-', 0, 12),
        ], $items);

        $this->table($headers, $rows);
    }
}
