<?php

namespace App\Services\Kgb;

use App\Enums\KgbStatus;
use App\Exceptions\ApiError;
use App\Exceptions\ApiErrorCode;
use App\Models\Opd;
use App\Models\RiwayatKgb;
use App\Services\Pmk\PmkService;
use App\Services\SimAsn\SimAsnService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class KgbService
{
    public function __construct(
        private readonly SimAsnService $simAsnService,
        private readonly PmkService $pmkService,
        private readonly KgbCalculationService $calculationService,
        private readonly KgbSnapshotService $snapshotService,
        private readonly KgbDocumentService $documentService,
        private readonly \App\Services\Audit\AuditService $auditService,
    ) {}

    /**
     * List KGB records with pagination and filtering.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = RiwayatKgb::query()
            ->with(['opd', 'snapshot', 'pmk', 'refGaji'])
            ->byStatus($filters['status'] ?? null)
            ->byOpd($filters['opd_id'] ?? null)
            ->byPegawai($filters['pegawai_id'] ?? null)
            ->byTahun($filters['tahun'] ?? null)
            ->search($filters['search'] ?? null);

        // Sort
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir = $filters['dir'] ?? 'desc';
        $allowedSorts = [
            'created_at', 'updated_at', 'tmt_kgb_baru', 'pegawai_nama',
            'pegawai_nip', 'status', 'gaji_baru',
        ];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }
        $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Generate a new KGB draft for a given pegawai.
     */
    public function generateDraft(int $pegawaiId, ?int $pmkId = null): RiwayatKgb
    {
        return DB::transaction(function () use ($pegawaiId, $pmkId) {
            // 1. Get pegawai data from SIM-ASN
            $pegawai = $this->simAsnService->getPegawai($pegawaiId);

            // 2. Get last golongan from SIM-ASN
            $golongan = $this->simAsnService->getLastGolongan($pegawaiId);
            if (!$golongan) {
                throw ApiError::notFound(
                    'SIMASN_DATA_NOT_FOUND',
                    'Riwayat golongan tidak ditemukan di SIM-ASN',
                    ['pegawai_id' => $pegawaiId]
                );
            }

            $golonganKode = $golongan['kode_golongan'] ?? $golongan['golongan'] ?? null;
            if (!$golonganKode) {
                throw ApiError::notFound(
                    'SIMASN_DATA_NOT_FOUND',
                    'Kode golongan tidak ditemukan di SIM-ASN',
                    ['pegawai_id' => $pegawaiId]
                );
            }

            // 3. Determine masa kerja
            $masaKerjaTahun = $golongan['masa_kerja_tahun'] ?? 0;
            $masaKerjaBulan = $golongan['masa_kerja_bulan'] ?? 0;

            if ($pmkId) {
                $pmk = $this->pmkService->find($pmkId);
                if ($pmk) {
                    $masaKerjaTahun = $pmk->masa_kerja_baru_tahun ?? $masaKerjaTahun;
                    $masaKerjaBulan = $pmk->masa_kerja_baru_bulan ?? $masaKerjaBulan;
                }
            }

            // 4. Calculate gaji baru
            $calculatedGaji = $this->calculationService->calculateGajiBaru(
                $golonganKode,
                $masaKerjaTahun,
                $masaKerjaBulan
            );

            // 5. Find last KGB for tmt_lama and gaji_lama
            $lastKgb = RiwayatKgb::query()
                ->where('pegawai_id', $pegawaiId)
                ->orderByDesc('tmt_kgb_baru')
                ->first();

            $tmtLama = $lastKgb?->tmt_kgb_baru
                ?? Carbon::parse($golongan['tmt'] ?? now()->subYear()->format('Y-m-d'));
            $gajiLama = $lastKgb?->gaji_baru
                ?? 0;

            // 6. Calculate tmt_baru
            $tmtBaru = $this->calculationService->calculateTmtBaru($tmtLama);

            // 7. Determine opd_id
            $opdId = $pegawai['opd_id'] ?? null;

            // 8. Create RiwayatKgb record
            $kgb = RiwayatKgb::create([
                'pegawai_id' => $pegawaiId,
                'pegawai_nama' => $pegawai['nama'] ?? null,
                'pegawai_nip' => $pegawai['nip'] ?? null,
                'opd_id' => $opdId,
                'status' => KgbStatus::DRAFT,
                'golongan' => $golonganKode,
                'masa_kerja_tahun' => $calculatedGaji['masa_kerja_tahun'],
                'masa_kerja_bulan' => $calculatedGaji['masa_kerja_bulan'],
                'gaji_lama' => $gajiLama,
                'gaji_baru' => $calculatedGaji['gaji'],
                'tmt_kgb_lama' => $tmtLama,
                'tmt_kgb_baru' => $tmtBaru,
                'pmk_id' => $pmkId,
            ]);

            // 9. Store snapshot
            $this->snapshotService->store($kgb, $pegawai);

            // 10. Audit log
            $this->auditService->logKgb('GENERATED', $kgb, null, 'Draft KGB berhasil dibuat');

            return $kgb;
        });
    }

    /**
     * Find a KGB record by ID with eager loading.
     */
    public function find(int $id): RiwayatKgb
    {
        $kgb = RiwayatKgb::query()
            ->with(['opd', 'snapshot', 'pmk', 'refGaji'])
            ->find($id);

        if (!$kgb) {
            throw ApiError::notFound('KGB_NOT_FOUND', 'Data KGB tidak ditemukan', ['id' => $id]);
        }

        return $kgb;
    }

    /**
     * Update a KGB record.
     */
    public function update(int $id, array $data): RiwayatKgb
    {
        $kgb = $this->find($id);

        if (!$kgb->isEditable()) {
            throw ApiError::notFound('KGB_CANNOT_MODIFY', 'KGB sudah diajukan/diverifikasi/disetujui, tidak bisa diubah', ['id' => $id]);
        }

        $oldData = $kgb->toArray();
        $kgb->update(array_intersect_key($data, array_flip($kgb->getFillable())));

        $this->auditService->logKgb('UPDATED', $kgb, $oldData);

        return $kgb;
    }

    /**
     * Delete a KGB record.
     */
    public function delete(int $id): bool
    {
        $kgb = $this->find($id);

        if (!$kgb->isDeletable()) {
            throw ApiError::notFound('KGB_CANNOT_MODIFY', 'KGB sudah diajukan/diverifikasi/disetujui, tidak bisa dihapus', ['id' => $id]);
        }

        $oldData = $kgb->toArray();
        $kgb->delete();

        $this->auditService->logKgb('DELETED', $kgb, $oldData, 'KGB berhasil dihapus');

        return true;
    }

    /**
     * Submit a KGB record (DRAFT -> DIAJUKAN).
     */
    public function submit(int $id, ?string $notes = null): RiwayatKgb
    {
        $kgb = $this->find($id);

        if ($kgb->status !== KgbStatus::DRAFT) {
            throw ApiError::invalidTransition('KGB hanya bisa diajukan dari status DRAFT');
        }

        $kgb->update(['status' => KgbStatus::DIAJUKAN]);
        if ($notes) {
            $kgb->update(['notes' => $notes]);
        }

        $this->auditService->logKgb('SUBMITTED', $kgb, null, $notes ?? 'KGB diajukan');

        return $kgb;
    }

    /**
     * Verify a KGB record (DIAJUKAN -> DIVERIFIKASI or DITOLAK).
     */
    public function verify(int $id, string $action, ?string $notes = null): RiwayatKgb
    {
        $kgb = $this->find($id);

        if ($kgb->status !== KgbStatus::DIAJUKAN) {
            throw ApiError::invalidTransition('KGB hanya bisa diverifikasi dari status DIAJUKAN');
        }

        if ($action === 'approve') {
            $kgb->update([
                'status' => KgbStatus::DIVERIFIKASI,
                'notes' => $notes,
            ]);
            $this->auditService->logKgb('VERIFIED', $kgb, null, $notes ?? 'KGB diverifikasi');
        } elseif ($action === 'reject') {
            $kgb->update([
                'status' => KgbStatus::DITOLAK,
                'notes' => $notes,
            ]);
            $this->auditService->logKgb('REJECTED', $kgb, null, $notes ?? 'KGB ditolak');
        } else {
            throw ApiError::invalidTransition("Action verifikasi tidak valid: {$action}");
        }

        return $kgb;
    }

    /**
     * Approve a KGB record (DIVERIFIKASI -> DISETUJUI).
     */
    public function approve(int $id, ?string $notes = null): RiwayatKgb
    {
        $kgb = $this->find($id);

        if ($kgb->status !== KgbStatus::DIVERIFIKASI) {
            throw ApiError::invalidTransition('KGB hanya bisa disetujui dari status DIVERIFIKASI');
        }

        $kgb->update([
            'status' => KgbStatus::DISETUJUI,
            'notes' => $notes,
        ]);

        $this->auditService->logKgb('APPROVED', $kgb, null, $notes ?? 'KGB disetujui');

        return $kgb;
    }

    /**
     * Reject a KGB record (any state -> DITOLAK).
     */
    public function reject(int $id, string $reason): RiwayatKgb
    {
        $kgb = $this->find($id);

        if ($kgb->status === KgbStatus::DISETUJUI || $kgb->status === KgbStatus::DITOLAK) {
            throw ApiError::invalidTransition('KGB yang sudah disetujui/ditolak tidak bisa ditolak lagi');
        }

        $kgb->update([
            'status' => KgbStatus::DITOLAK,
            'notes' => $reason,
        ]);

        $this->auditService->logKgb('REJECTED', $kgb, null, $reason);

        return $kgb;
    }

    /**
     * Get snapshot data for a KGB record.
     */
    public function getSnapshot(int $id): array
    {
        $kgb = $this->find($id);
        $snapshot = $this->snapshotService->get($kgb);
        return $snapshot?->data_json ?? [];
    }

    /**
     * Get SK document URL for a KGB record.
     */
    public function getDocumentUrl(int $id): ?string
    {
        $kgb = $this->find($id);
        return $this->documentService->getSkUrl($kgb);
    }
}