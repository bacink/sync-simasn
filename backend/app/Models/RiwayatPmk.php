<?php

namespace App\Models;

use App\Enums\PmkStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiwayatPmk extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'riwayat_pmk';

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
        'no_sk',
        'tanggal_sk',
        'masa_kerja_lama_tahun',
        'masa_kerja_lama_bulan',
        'masa_kerja_baru_tahun',
        'masa_kerja_baru_bulan',
        'file_sk_id',
        'keterangan',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PmkStatus::class,
            'tanggal_sk' => 'date',
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

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isDeletable(): bool
    {
        return $this->status->isDeletable();
    }
}