<?php

namespace App\Services\SimAsn;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use SIM_ASN\AppClient;

class SimAsnService
{
    /**
     * SimAsnService constructor.
     */
    public function __construct(
        protected AppClient $client
    ) {}

    /**
     * Get basic employee data.
     */
    public function getPegawai(int|string $id): array
    {
        $pegawai = $this->client->pegawai()->getDetail($id);

        return $pegawai ? $pegawai->toArray() : [];
    }

    /**
     * Get employee rank/class (golongan) history.
     */
    public function getRiwayatGolongan(int|string $pegawaiId): array
    {
        $riwayat = $this->client->pegawai()->getRiwayatGolongan($pegawaiId);

        return $riwayat ? $riwayat->toArray() : [];
    }

    /**
     * Get employee position (jabatan) history.
     */
    public function getRiwayatJabatan(int|string $pegawaiId): array
    {
        $riwayat = $this->client->pegawai()->getRiwayatJabatan($pegawaiId);

        return $riwayat ? $riwayat->toArray() : [];
    }

    /**
     * Get the latest rank/class (golongan) for an employee.
     */
    public function getLastGolongan(int|string $pegawaiId): ?array
    {
        $riwayat = $this->client->pegawai()->getRiwayatGolongan($pegawaiId);

        if (!$riwayat || $riwayat->isEmpty()) {
            return null;
        }

        // Return the first/latest record as an array
        return $riwayat->first()?->toArray();
    }

    /**
     * Search for employees.
     */
    public function searchPegawai(string $query, ?string $jenis = null, int $limit = 100): array
    {
        $filters = ['search' => $query, 'limit' => $limit];
        if ($jenis !== null) {
            $filters['jenis'] = $jenis;
        }

        $results = $this->client->pegawai()->getList($filters);

        return collect($results->items())->map(function ($item) {
            return is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
        })->all();
    }

    /**
     * List all employees.
     * we can get pagination metadata from
     * $results->currentPage(),
     * $results->perPage(),
     * $results->total(),
     * $results->lastPage(),
     * etc. if needed
     */
    public function listPegawai(?string $jenis = null, int $limit = 100): array
    {
        $filters = ['limit' => $limit];
        if ($jenis !== null) {
            $filters['jenis'] = $jenis;
        }

        $results = $this->client->pegawai()->getList($filters);

        return collect($results->items())->map(function ($item) {
            return is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
        })->all();
    }

    /**
     * List all employees and return paginator for metadata.
     *
     * @param int $page 1-based page number (default 1)
     */
    public function listPegawaiPaginator(?string $jenis = null, int $limit = 100, int $page = 1): LengthAwarePaginator
    {
        $filters = ['limit' => $limit, 'page' => $page];
        if ($jenis !== null) {
            $filters['jenis'] = $jenis;
        }

        return $this->client->pegawai()->getList($filters);
    }

    /**
     * Get employee documents (dokumen) from SIM-ASN.
     *
     * @return array<array{id, jenis_dokumen, nama, file_url, ...}>
     */
    public function getDokumen(int|string $pegawaiId): array
    {
        $result = $this->client->pegawai()->getDokumen($pegawaiId);

        if ($result instanceof LengthAwarePaginator) {
            return array_map(fn($item) => $item->toArray(), $result->items());
        }

        return array_map(fn($item) => $item->toArray(), $result->all());
    }

    /**
     * Get employee files (file) from SIM-ASN.
     *
     * @return array<array{id, jenis, nama, url, ...}>
     */
    public function getFile(int|string $pegawaiId): array
    {
        $result = $this->client->pegawai()->getFile($pegawaiId);

        if ($result instanceof LengthAwarePaginator) {
            return array_map(fn($item) => $item->toArray(), $result->items());
        }

        return array_map(fn($item) => $item->toArray(), $result->all());
    }
}
