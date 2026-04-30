<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiwayatPmk extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'riwayat_pmk';

    protected $fillable = [
        'pegawai_id',
        'nip',
        'nama',
        'masa_kerja_lama_tahun',
        'masa_kerja_lama_bulan',
        'masa_kerja_baru_tahun',
        'masa_kerja_baru_bulan',
        'dasar_pmk',
        'nomor_sk',
        'tanggal_sk',
        'file_id',
    ];

    protected $casts = [
        'pegawai_id' => 'string',
        'masa_kerja_lama_tahun' => 'integer',
        'masa_kerja_lama_bulan' => 'integer',
        'masa_kerja_baru_tahun' => 'integer',
        'masa_kerja_baru_bulan' => 'integer',
        'tanggal_sk' => 'date',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public function riwayatKgb(): HasMany
    {
        return $this->hasMany(RiwayatKgb::class, 'pmk_id');
    }
}
