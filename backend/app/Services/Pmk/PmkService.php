<?php

namespace App\Services\Pmk;

use App\Enums\PmkStatus;
use App\Exceptions\ApiError;
use App\Models\RiwayatPmk;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PmkService
{
    public function __construct(
        private readonly \App\Services\SimAsn\SimAsnService $simAsnService,
        private readonly \App\Services\Audit\AuditService $auditService,
    ) {}

    /**
     * List PMK records with pagination and filtering.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = RiwayatPmk::query()
            ->with(['opd'])
            ->byPegawai($filters['pegawai_id'] ?? null)
            ->byTahun($filters['tahun'] ?? null)
            ->byStatus($filters['status'] ?? null)
            ->search($filters['search'] ?? null);

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir = $filters['dir'] ?? 'desc';
        $allowedSorts = ['created_at', 'updated_at', 'tanggal_sk', 'pegawai_nama', 'pegawai_nip', 'status', 'no_sk'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }
        $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new PMK record.
     */
    public function create(array $data): RiwayatPmk
    {
        return DB::transaction(function () use ($data) {
            $pegawaiId = $data['pegawai_id'];

            // Get pegawai from SIM-ASN
            $pegawai = $this->simAsnService->getPegawai($pegawaiId);

            $pmk = RiwayatPmk::create([
                'pegawai_id' => $pegawaiId,
                'pegawai_nama' => $pegawai['nama'] ?? null,
                'pegawai_nip' => $pegawai['nip'] ?? null,
                'opd_id' => $pegawai['opd_id'] ?? null,
                'status' => PmkStatus::DRAFT,
                'no_sk' => $data['no_sk'] ?? null,
                'tanggal_sk' => $data['tanggal_sk'] ?? null,
                'masa_kerja_lama_tahun' => $data['masa_kerja_lama_tahun'] ?? 0,
                'masa_kerja_lama_bulan' => $data['masa_kerja_lama_bulan'] ?? 0,
                'masa_kerja_baru_tahun' => $data['masa_kerja_baru_tahun'] ?? 0,
                'masa_kerja_baru_bulan' => $data['masa_kerja_baru_bulan'] ?? 0,
                'file_sk_id' => $data['file_sk_id'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            $this->auditService->logPmk('CREATED', $pmk, null, 'PMK berhasil dibuat');

            return $pmk;
        });
    }

    /**
     * Find a PMK record by ID.
     */
    public function find(int $id): RiwayatPmk
    {
        $pmk = RiwayatPmk::query()
            ->with(['opd'])
            ->find($id);

        if (!$pmk) {
            throw ApiError::notFound('PMK_NOT_FOUND', 'Data PMK tidak ditemukan', ['id' => $id]);
        }

        return $pmk;
    }

    /**
     * Update a PMK record.
     */
    public function update(int $id, array $data): RiwayatPmk
    {
        $pmk = $this->find($id);

        if (!$pmk->isEditable()) {
            throw ApiError::notFound(
                'PMK_VALIDATION_FAILED',
                'PMK sudah aktif/nonaktif, tidak bisa diedit',
                ['id' => $id]
            );
        }

        $oldData = $pmk->toArray();
        $pmk->update(array_intersect_key($data, array_flip($pmk->getFillable())));

        $this->auditService->logPmk('UPDATED', $pmk, $oldData);

        return $pmk->fresh(['opd']);
    }

    /**
     * Delete a PMK record.
     */
    public function delete(int $id): bool
    {
        $pmk = $this->find($id);

        if (!$pmk->isDeletable()) {
            throw ApiError::notFound(
                'PMK_VALIDATION_FAILED',
                'PMK sudah aktif/nonaktif, tidak bisa dihapus',
                ['id' => $id]
            );
        }

        $oldData = $pmk->toArray();
        $pmk->delete();

        $this->auditService->logPmk('DELETED', $pmk, $oldData, 'PMK berhasil dihapus');

        return true;
    }
}