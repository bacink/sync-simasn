<?php

namespace App\Services\Kgb;

use App\DTOs\KgbApprovalDTO;
use App\DTOs\KgbGenerateDTO;
use App\DTOs\KgbSubmitDTO;
use App\Enums\JenisAsn;
use App\Enums\KgbStatus;
use App\Enums\KgbType;
use App\Models\File;
use App\Models\KgbApproval;
use App\Models\KgbCalculation;
use App\Models\RefGolongan;
use App\Models\RiwayatKgb;
use App\Services\AuditService;
use App\Services\Pmk\PmkService;
use App\Services\SimAsn\SimAsnService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KgbService
{
    public function __construct(
        protected SimAsnService $simAsnService,
        protected KgbCalculationService $calculationService,
        protected KgbSnapshotService $snapshotService,
        protected PmkService $pmkService,
        protected AuditService $auditService,
        protected PeraturanResolverService $peraturanResolver,
    ) {}

    public function generateDraft(KgbGenerateDTO $dto): RiwayatKgb
    {
        $pegawaiId = $dto->pegawaiId;
        $pegawai = $this->simAsnService->getPegawai($pegawaiId);
        $lastGolongan = $this->simAsnService->getLastGolongan($pegawaiId);

        if (! $lastGolongan) {
            throw new \RuntimeException('Pegawai belum memiliki riwayat golongan');
        }

        $calcData = $this->prepareCalculationData($pegawai, $lastGolongan);
        $activePeraturan = $this->peraturanResolver->getActive();

        // Resolve golongan FK
        $golonganFk = RefGolongan::query()
            ->where('golongan', $lastGolongan['golongan'])
            ->where('jenis_asn', $pegawai['jenis_asn'] ?? JenisAsn::PNS->value)
            ->first();

        return DB::transaction(function () use ($pegawai, $lastGolongan, $calcData, $pegawaiId, $activePeraturan, $golonganFk) {
            $kgb = RiwayatKgb::create([
                'pegawai_id' => $pegawaiId,
                'golongan_id' => $golonganFk?->id,
                'peraturan_id' => $activePeraturan->id,
                'masa_kerja_tahun' => $calcData['masa_kerja'],
                'masa_kerja_bulan' => $calcData['masa_kerja_bulan'],
                'gaji_lama' => $calcData['gaji_lama']?->gaji ?? 0,
                'gaji_baru' => $calcData['gaji_baru']->gaji,
                'tmt_kgb' => now()->addYears(2)->format('Y-m-d'),
                'jenis_kgb' => KgbType::Reguler,
                'status' => KgbStatus::Draft,
                'pmk_id' => $calcData['pmk']?->id,
            ]);

            KgbCalculation::create([
                'riwayat_kgb_id' => $kgb->id,
                'golongan' => $calcData['gaji_baru']->golongan,
                'masa_kerja' => $calcData['masa_kerja_baru'],
                'gaji_ref_id' => $calcData['gaji_baru']->id,
                'gaji_hasil' => $calcData['gaji_baru']->gaji,
                'formula' => "golongan={$calcData['gaji_baru']->golongan}, masa_kerja={$calcData['masa_kerja_baru']}",
            ]);

            $this->snapshotService->store($kgb, $pegawai, $activePeraturan->id, $calcData['gaji_baru']->gaji);
            $this->auditService->log('riwayat_kgb', $kgb->id, 'create_draft', null, $kgb->toArray());

            Log::info('KgbService: KGB Draft generated', ['pegawai_id' => $pegawaiId, 'riwayat_kgb_id' => $kgb->id]);

            return $kgb;
        });
    }

    /**
     * @param array{pegawai: array, lastGolongan: array} $params
     * @return array{masa_kerja: int, masa_kerja_bulan: int, masa_kerja_baru: int, gaji_lama: mixed, gaji_baru: mixed, pmk: mixed}
     */
    protected function prepareCalculationData(array $pegawai, array $lastGolongan): array
    {
        $masaKerja = (int) ($lastGolongan['masa_kerja_tahun'] ?? 0);
        $masaKerjaBulan = (int) ($lastGolongan['masa_kerja_bulan'] ?? 0);

        $pmk = $this->pmkService->getLatestForPegawai($pegawai['id']);
        if ($pmk) {
            $masaKerja = $pmk->masa_kerja_baru_tahun;
            $masaKerjaBulan = $pmk->masa_kerja_baru_bulan;
        }

        $masaKerjaBaru = $masaKerja + 2;
        $jenisAsn = str_contains(strtolower($pegawai['status_pegawai'] ?? ''), 'pppk') ? JenisAsn::PPPK : JenisAsn::PNS;

        $gajiLama = $this->calculationService->calculate($jenisAsn, $lastGolongan['golongan'], $masaKerja);
        $gajiBaru = $this->calculationService->calculate($jenisAsn, $lastGolongan['golongan'], $masaKerjaBaru);

        if (! $gajiBaru) {
            throw new \RuntimeException('Referensi gaji tidak ditemukan');
        }

        return [
            'masa_kerja' => $masaKerja,
            'masa_kerja_bulan' => $masaKerjaBulan,
            'masa_kerja_baru' => $masaKerjaBaru,
            'gaji_lama' => $gajiLama,
            'gaji_baru' => $gajiBaru,
            'pmk' => $pmk,
        ];
    }

    public function submit(RiwayatKgb $kgb, KgbSubmitDTO $dto): RiwayatKgb
    {
        if ($kgb->status !== KgbStatus::Draft) {
            throw new \RuntimeException('KGB hanya bisa diajukan dari status draft');
        }

        return DB::transaction(function () use ($kgb, $dto) {
            $oldData = $kgb->toArray();

            $kgb->update([
                'status' => KgbStatus::Diajukan,
                'nomor_sk' => $dto->nomorSk,
                'tanggal_sk' => $dto->tanggalSk,
            ]);

            $this->createApprovalRecord($kgb, 'operator', KgbStatus::Diajukan);
            $this->auditService->log('riwayat_kgb', $kgb->id, 'submit', $oldData, $kgb->toArray());

            return $kgb;
        });
    }

    public function verify(RiwayatKgb $kgb, KgbApprovalDTO $dto): RiwayatKgb
    {
        if ($kgb->status !== KgbStatus::Diajukan) {
            throw new \RuntimeException('KGB hanya bisa diverifikasi dari status diajukan');
        }

        return DB::transaction(function () use ($kgb, $dto) {
            $oldData = $kgb->toArray();
            $kgb->update(['status' => KgbStatus::Diverifikasi]);

            $this->createApprovalRecord($kgb, 'verifikator', KgbStatus::Diverifikasi, $dto->catatan);
            $this->auditService->log('riwayat_kgb', $kgb->id, 'verify', $oldData, $kgb->toArray());

            return $kgb;
        });
    }

    public function approve(RiwayatKgb $kgb): RiwayatKgb
    {
        if ($kgb->status !== KgbStatus::Diverifikasi) {
            throw new \RuntimeException('KGB hanya bisa disetujui dari status diverifikasi');
        }

        return DB::transaction(function () use ($kgb) {
            $oldData = $kgb->toArray();
            $kgb->update(['status' => KgbStatus::Disetujui]);

            $this->createApprovalRecord($kgb, 'admin', KgbStatus::Disetujui);
            $this->auditService->log('riwayat_kgb', $kgb->id, 'approve', $oldData, $kgb->toArray());

            return $kgb;
        });
    }

    public function reject(RiwayatKgb $kgb, KgbApprovalDTO $dto): RiwayatKgb
    {
        if (! in_array($kgb->status, [KgbStatus::Diajukan, KgbStatus::Diverifikasi])) {
            throw new \RuntimeException('KGB tidak bisa ditolak dari status saat ini');
        }

        return DB::transaction(function () use ($kgb, $dto) {
            $oldData = $kgb->toArray();
            $kgb->update(['status' => KgbStatus::Ditolak]);

            $this->createApprovalRecord($kgb, 'reviewer', KgbStatus::Ditolak, $dto->catatan);
            $this->auditService->log('riwayat_kgb', $kgb->id, 'reject', $oldData, $kgb->toArray());

            return $kgb;
        });
    }

    protected const STEP_ORDER = [
        'operator' => 1,
        'verifikator' => 2,
        'admin' => 3,
        'reviewer' => 4,
    ];

    protected function createApprovalRecord(RiwayatKgb $kgb, string $role, KgbStatus $status, ?string $catatan = null): void
    {
        KgbApproval::create([
            'riwayat_kgb_id' => $kgb->id,
            'user_id' => Auth::id(),
            'role' => $role,
            'step_order' => self::STEP_ORDER[$role] ?? 1,
            'catatan' => $catatan,
        ]);
    }

    public function findOrFail(int|string $id): RiwayatKgb
    {
        $kgb = RiwayatKgb::with(['snapshot', 'calculation', 'pmk', 'approvals.user', 'golongan'])->find($id);

        if (! $kgb) {
            throw new \RuntimeException('KGB tidak ditemukan');
        }

        return $kgb;
    }

    public function getList(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = RiwayatKgb::with(['snapshot', 'golongan', 'peraturan']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['pegawai_id'])) {
            $query->where('pegawai_id', $filters['pegawai_id']);
        }

        if (! empty($filters['nip'])) {
            $query->whereHas('snapshot', fn($q) => $q->whereRaw("json_extract(data_json, '$.nip') LIKE ?", ["%{$filters['nip']}%"]));
        }

        if (! empty($filters['tahun'])) {
            $query->whereYear('tmt_kgb', $filters['tahun']);
        }

        return $query->orderByDesc('created_at')->get();
    }
}
