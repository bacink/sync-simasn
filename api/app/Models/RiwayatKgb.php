<?php

namespace App\Models;

use App\Enums\KgbStatus;
use App\Enums\KgbType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiwayatKgb extends Model
{
    /** @use HasFactory<\Database\Factories\RiwayatKgbFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'riwayat_kgb';

    protected $fillable = [
        'pegawai_id',
        'golongan_id',
        'peraturan_id',
        'masa_kerja_tahun',
        'masa_kerja_bulan',
        'gaji_lama',
        'gaji_baru',
        'tmt_kgb',
        'nomor_sk',
        'tanggal_sk',
        'jenis_kgb',
        'status',
        'pmk_id',
    ];

    protected function casts(): array
    {
        return [
            'tmt_kgb' => 'date',
            'tanggal_sk' => 'date',
            'jenis_kgb' => KgbType::class,
            'status' => KgbStatus::class,
            'masa_kerja_tahun' => 'integer',
            'masa_kerja_bulan' => 'integer',
            'gaji_lama' => 'decimal:2',
            'gaji_baru' => 'decimal:2',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /** Single source of truth for employee data at creation time */
    public function snapshot(): HasOne
    {
        return $this->hasOne(KgbSnapshot::class, 'riwayat_kgb_id');
    }

    /** Salary calculation result */
    public function calculation(): HasOne
    {
        return $this->hasOne(KgbCalculation::class, 'riwayat_kgb_id');
    }

    /** Approval workflow history */
    public function approvals(): HasMany
    {
        return $this->hasMany(KgbApproval::class, 'riwayat_kgb_id');
    }

    /** Polymorphic: multiple files (SK, lampiran, etc.) */
    public function files(): MorphMany
    {
        return $this->morphMany(Fileable::class, 'fileable')
            ->orderBy('kategori');
    }

    public function golongan(): BelongsTo
    {
        return $this->belongsTo(RefGolongan::class, 'golongan_id');
    }

    public function peraturan(): BelongsTo
    {
        return $this->belongsTo(RefPeraturan::class, 'peraturan_id');
    }

    public function pmk(): BelongsTo
    {
        return $this->belongsTo(RiwayatPmk::class, 'pmk_id');
    }

    public function refStatus(): BelongsTo
    {
        return $this->belongsTo(RefStatusKgb::class, 'status', 'kode');
    }

    // ─── Helpers ─────────────────────────────────────────────

    public function isEditable(): bool
    {
        return $this->status === KgbStatus::Draft;
    }

    public function getGajiNaikAttribute(): float
    {
        return max(0, (float) $this->gaji_baru - (float) $this->gaji_lama);
    }
}