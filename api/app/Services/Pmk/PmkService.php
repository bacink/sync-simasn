<?php

namespace App\Services\Pmk;

use App\Models\File;
use App\Models\RiwayatPmk;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for managing Work Period Review (PMK) records.
 * PMK data is used to adjust the baseline work period for KGB calculations.
 */
class PmkService
{
    /**
     * Store a new PMK record.
     *
     * @param array<string, mixed> $data
     * @return RiwayatPmk
     */
    public function store(array $data): RiwayatPmk
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['file_sk']) && $data['file_sk'] instanceof UploadedFile) {
                $file = $this->storeFile($data['file_sk'], 'pmk/sk');
                $data['file_id'] = $file->id;
                unset($data['file_sk']);
            }

            $pmk = RiwayatPmk::create($data);

            Log::info('PmkService: PMK record created successfully.', [
                'pegawai_id' => $pmk->pegawai_id,
                'nip'        => $pmk->nip,
                'sk_nomor'   => $pmk->nomor_sk,
            ]);

            return $pmk;
        });
    }

    /**
     * Update an existing PMK record.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return RiwayatPmk
     */
    public function update(int $id, array $data): RiwayatPmk
    {
        return DB::transaction(function () use ($id, $data) {
            $pmk = RiwayatPmk::findOrFail($id);
            $pmk->update($data);

            Log::info('PmkService: PMK record updated.', [
                'pmk_id' => $id,
                'nip'    => $pmk->nip,
            ]);

            return $pmk;
        });
    }

    /**
     * Delete a PMK record.
     *
     * @param int $id
     * @return bool|null
     */
    public function delete(int $id): ?bool
    {
        return DB::transaction(function () use ($id) {
            $pmk = RiwayatPmk::findOrFail($id);
            return $pmk->delete();
        });
    }

    /**
     * Retrieve the most recent PMK record for a specific employee.
     *
     * @param int $pegawaiId
     * @return RiwayatPmk|null
     */
    public function getLatestForPegawai(int $pegawaiId): ?RiwayatPmk
    {
        return RiwayatPmk::query()
            ->where('pegawai_id', $pegawaiId)
            ->orderByDesc('tanggal_sk')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Get a list of PMK records with optional filters.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, RiwayatPmk>
     */
    public function getList(array $filters = []): Collection
    {
        $query = RiwayatPmk::query()->with('file');

        if (! empty($filters['pegawai_id'])) {
            $query->where('pegawai_id', $filters['pegawai_id']);
        }

        if (! empty($filters['nip'])) {
            $query->where('nip', 'like', "%{$filters['nip']}%");
        }

        return $query->orderByDesc('tanggal_sk')->orderByDesc('id')->get();
    }

    /**
     * Find a PMK record or throw an exception.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): RiwayatPmk
    {
        return RiwayatPmk::with('file')->findOrFail($id);
    }

    /**
     * Store uploaded file and create file record.
     */
    protected function storeFile(UploadedFile $uploadedFile, string $folder): File
    {
        $path = $uploadedFile->store($folder, 'public');

        return File::create([
            'path' => $path,
            'name' => basename($path),
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
        ]);
    }
}
