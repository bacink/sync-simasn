<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KgbCalculation extends Model
{
    use SoftDeletes;

    protected $table = 'kgb_calculations';

    protected $fillable = [
        'riwayat_kgb_id',
        'golongan',
        'masa_kerja',
        'gaji_ref_id',
        'gaji_hasil',
        'formula',
    ];

    protected $casts = [
        'masa_kerja' => 'integer',
        'gaji_hasil' => 'decimal:2',
    ];

    public function riwayatKgb(): BelongsTo
    {
        return $this->belongsTo(RiwayatKgb::class, 'riwayat_kgb_id');
    }

    public function gajiRef(): BelongsTo
    {
        return $this->belongsTo(RefGajiAsn::class, 'gaji_ref_id');
    }
}
