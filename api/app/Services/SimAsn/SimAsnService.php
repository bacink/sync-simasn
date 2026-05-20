<?php

namespace App\Services\SimAsn;

use App\Exceptions\ApiError;
use App\Exceptions\ApiErrorCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class SimAsnService
{
    private ?string $baseUrl;
    private ?string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('sim-asn.base_url');
        $this->apiKey = config('sim-asn.api_key');
        $this->timeout = (int) config('sim-asn.timeout', 30);
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout($this->timeout)
            ->when($this->apiKey, fn($r) => $r->withHeaders(['X-API-Key' => $this->apiKey]));
    }

    public function getPegawai(int $id): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/pegawai/{$id}");
            $response->throw();
            return $response->json();
        } catch (RequestException $e) {
            Log::error('SIM-ASN getPegawai failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw new ApiError(ApiErrorCode::SIMASN_DATA_NOT_FOUND, [], 'Data pegawai tidak ditemukan di SIM-ASN', 404);
        } catch (\Exception $e) {
            throw new ApiError(ApiErrorCode::SIMASN_CONNECTION_FAILED);
        }
    }

    public function getPegawaiList(array $params = []): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/pegawai", $params);
            $response->throw();
            return $response->json();
        } catch (RequestException $e) {
            throw new ApiError(ApiErrorCode::SIMASN_CONNECTION_FAILED);
        }
    }

    public function getRiwayatGolongan(int $pegawaiId): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/pegawai/{$pegawaiId}/golongan");
            $response->throw();
            return $response->json();
        } catch (RequestException $e) {
            throw new ApiError(ApiErrorCode::SIMASN_CONNECTION_FAILED);
        }
    }

    public function getLastGolongan(int $pegawaiId): ?array
    {
        $riwayat = $this->getRiwayatGolongan($pegawaiId);
        if (empty($riwayat)) return null;
        return collect($riwayat)->sortByDesc('tmt')->first();
    }

    public function getRiwayatJabatan(int $pegawaiId): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/pegawai/{$pegawaiId}/jabatan");
            $response->throw();
            return $response->json();
        } catch (RequestException $e) {
            throw new ApiError(ApiErrorCode::SIMASN_CONNECTION_FAILED);
        }
    }
}
