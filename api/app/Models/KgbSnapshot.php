<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KgbSnapshot extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kgb_snapshots';

    protected $fillable = [
        'riwayat_kgb_id',
        'peraturan_id',
        'jabatan_nama',
        'unit_kerja',
        'nama_skpd',
        'golongan',
        'masa_kerja_tahun',
        'masa_kerja_bulan',
        'gaji_pokok',
        'data_json',
    ];

    protected function casts(): array
    {
        return [
            'data_json' => 'array',
            'masa_kerja_tahun' => 'integer',
            'masa_kerja_bulan' => 'integer',
            'gaji_pokok' => 'decimal:2',
        ];
    }

    public function riwayatKgb(): BelongsTo
    {
        return $this->belongsTo(RiwayatKgb::class, 'riwayat_kgb_id');
    }

    /** FK back to the regulation used at snapshot time */
    public function peraturan(): BelongsTo
    {
        return $this->belongsTo(RefPeraturan::class, 'peraturan_id');
    }

    /** Get raw JSON value by key */
    public function getRawData(string $key, mixed $default = null): mixed
    {
        return data_get($this->data_json, $key, $default);
    }
}
