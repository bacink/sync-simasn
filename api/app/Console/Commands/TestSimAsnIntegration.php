<?php

namespace App\Console\Commands;

use App\Services\SimAsn\SimAsnService;
use Illuminate\Console\Command;

class TestSimAsnIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sim-asn {search=test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SIM-ASN integration by fetching employee list';

    /**
     * Execute the console command.
     */
    public function handle(SimAsnService $service)
    {
        $search = $this->argument('search');
        $this->info("Testing SIM-ASN Integration...");
        $this->info("Searching for: {$search}");

        try {
            $results = $service->searchPegawai($search);

            if (empty($results)) {
                $this->warn("No results found or connection failed.");
                return 1;
            }

            $this->info("Successfully fetched " . count($results) . " records.");

            $headers = ['UUID', 'NIP', 'Nama', 'Golongan', 'Status'];
            $rows = array_map(function ($p) {
                // Handle nested objects from SIM-ASN SDK
                $golongan = $p['golongan'] ?? null;

                if (is_array($golongan)) {
                    $golLabel = $golongan['nama'] ?? $golongan['golongan'] ?? '-';
                } elseif (is_object($golongan)) {
                    $golLabel = $golongan->nama ?? $golongan->golongan ?? '-';
                } else {
                    $golLabel = $golongan ?? '-';
                }

                return [
                    substr($p['id'] ?? '-', 0, 8) . '...', // Shorten UUID for display
                    $p['nip'] ?? '-',
                    $p['nama'] ?? '-',
                    $golLabel,
                    $p['status_pegawai'] ?? $p['jenis'] ?? '-',
                ];
            }, $results);

            $this->table($headers, $rows);

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
