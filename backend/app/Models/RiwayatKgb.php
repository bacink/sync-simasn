<?php

namespace App\Models;

use App\Enums\KgbStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiwayatKgb extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'riwayat_kgb';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'pegawai_id',
        'pegawai_nama',
        'pegawai_nip',
        'opd_id',
        'status',
        'golongan',
        'masa_kerja_tahun',
        'masa_kerja_bulan',
        'gaji_lama',
        'gaji_baru',
        'tmt_kgb_lama',
        'tmt_kgb_baru',
        'pmk_id',
        'ref_gaji_id',
        'file_sk_id',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => KgbStatus::class,
            'gaji_lama' => 'decimal:0',
            'gaji_baru' => 'decimal:0',
            'tmt_kgb_lama' => 'date',
            'tmt_kgb_baru' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(KgbSnapshot::class);
    }

    public function pmk(): BelongsTo
    {
        return $this->belongsTo(RiwayatPmk::class, 'pmk_id');
    }

    public function refGaji(): BelongsTo
    {
        return $this->belongsTo(RefGajiAsn::class, 'ref_gaji_id');
    }

    /**
     * Check if the current status can transition to the given status.
     */
    public function canTransitionTo(KgbStatus $nextStatus): bool
    {
        return $this->status->canTransitionTo($nextStatus);
    }

    /**
     * Check if the KGB record is editable.
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }
}