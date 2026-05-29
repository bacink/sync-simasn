<?php

namespace App\Services\SimAsn;

use App\Exceptions\ApiError;
use App\Exceptions\ApiErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use SIM_ASN\AppClient;
use SIM_ASN\Models\Pegawai as SimAsnPegawai;
use SIM_ASN\Modules\Pegawai as PegawaiModule;

class SimAsnService
{
    private ?AppClient $client = null;

    public function __construct() {}

    private function client(): AppClient
    {
        if ($this->client === null) {
            $this->client = app(AppClient::class);
        }

        return $this->client;
    }

    private function pegawai(): PegawaiModule
    {
        return $this->client()->pegawai();
    }

    /**
     * Search employees by keyword, optionally filtered by jenis and limited.
     *
     * @param  string  $search  Search keyword (name, NIP, or partial text)
     * @param  string|null  $jenis  Employee type filter (pns|pppk|pppk_pw)
     * @param  int  $limit  Max results to return (0 = no limit)
     * @return array<int, array> Array of employee data arrays
     */
    public function searchPegawai(string $search, ?string $jenis = null, int $limit = 0): array
    {
        $params = array_filter([
            'search' => $search ?: null,
            'jenis' => $jenis,
        ], fn ($v) => $v !== null);

        $paginator = $this->pegawai()->getList($params);

        $items = $paginator instanceof LengthAwarePaginator
            ? $paginator->items()
            : $paginator->all();

        if ($limit > 0) {
            $items = array_slice($items, 0, $limit);
        }

        return $this->normalizePegawaiCollection($items);
    }

    /**
     * List all employees, optionally filtered by jenis and limited.
     *
     * @param  string|null  $jenis  Employee type filter
     * @param  int  $limit  Max results to return
     * @return array<int, array>
     */
    public function listPegawai(?string $jenis = null, int $limit = 0): array
    {
        return $this->searchPegawai('', $jenis, $limit);
    }

    /**
     * Return a paginator for all employees — used by archive-sync orchestrator.
     *
     * @param  string|null  $jenis  Employee type filter
     * @param  int  $perPage  Page size
     * @param  int  $page  Page number
     */
    public function listPegawaiPaginator(?string $jenis, int $perPage = 50, int $page = 1): LengthAwarePaginator
    {
        $params = array_filter([
            'jenis' => $jenis,
            'per_page' => $perPage,
            'page' => $page,
        ], fn ($v) => $v !== null);

        return $this->pegawai()->getList($params);
    }

    /**
     * Get single employee detail by ID or NIP.
     *
     * @param  int|string  $id  SIM-ASN employee ID or NIP
     */
    public function getPegawai(int|string $id): array
    {
        try {
            $detail = $this->pegawai()->getDetail($id);

            return $detail instanceof SimAsnPegawai
                ? $detail->toArray()
                : (is_object($detail) && method_exists($detail, 'toArray')
                    ? $detail->toArray()
                    : (array) $detail);
        } catch (RequestException $e) {
            Log::error('SIM-ASN getPegawai failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw new ApiError(ApiErrorCode::SIMASN_DATA_NOT_FOUND, [], 'Data pegawai tidak ditemukan di SIM-ASN', 404);
        } catch (\Exception $e) {
            throw new ApiError(ApiErrorCode::SIMASN_CONNECTION_FAILED);
        }
    }

    /**
     * List employees (passthrough for API controllers).
     *
     * @param  array  $params  Query parameters
     */
    public function getPegawaiList(array $params = []): mixed
    {
        return $this->pegawai()->getList($params);
    }

    /**
     * Get golongan (rank) history for an employee.
     *
     * @return array<int, array>
     */
    public function getRiwayatGolongan(int|string $pegawaiId): array
    {
        try {
            $collection = $this->pegawai()->getRiwayatGolongan($pegawaiId);

            return $this->normalizePegawaiCollection($collection);
        } catch (RequestException $e) {
            throw new ApiError(ApiErrorCode::SIMASN_CONNECTION_FAILED);
        }
    }

    /**
     * Get the latest golongan (rank) record for an employee.
     */
    public function getLastGolongan(int|string $pegawaiId): ?array
    {
        $riwayat = $this->getRiwayatGolongan($pegawaiId);
        if (empty($riwayat)) {
            return null;
        }

        return collect($riwayat)->sortByDesc('tmt')->first();
    }

    /**
     * Get jabatan (position) history for an employee.
     *
     * @return array<int, array>
     */
    public function getRiwayatJabatan(int|string $pegawaiId): array
    {
        try {
            $collection = $this->pegawai()->getRiwayatJabatan($pegawaiId);

            return $this->normalizePegawaiCollection($collection);
        } catch (RequestException $e) {
            throw new ApiError(ApiErrorCode::SIMASN_CONNECTION_FAILED);
        }
    }

    // ─── Internal helpers ───────────────────────────────────────────────────────

    /**
     * Normalize a collection / array of Pegawai models or plain arrays
     * into a plain array of arrays — ensuring consistent output shape
     * regardless of whether the SDK returned model instances or raw arrays.
     *
     * @return array<int, array>
     */
    private function normalizePegawaiCollection(array|Collection $items): array
    {
        return array_map(function (mixed $item): array {
            if ($item instanceof SimAsnPegawai) {
                return $item->toArray();
            }
            if (is_object($item) && method_exists($item, 'toArray')) {
                return $item->toArray();
            }

            return (array) $item;
        }, $items);
    }
}
