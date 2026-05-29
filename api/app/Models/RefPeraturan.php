<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RefPeraturan extends Model
{
    use HasFactory;

    protected $table = 'ref_peraturan';

    protected $fillable = [
        'jenis',
        'nomor',
        'tahun',
        'effective_date',
        'nama',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'effective_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function refGajiAsn(): HasMany
    {
        return $this->hasMany(RefGajiAsn::class, 'peraturan_id');
    }

    public function riwayatKgb(): HasMany
    {
        return $this->hasMany(RiwayatKgb::class, 'peraturan_id');
    }

    /** The most recent regulation (by effective_date) */
    public function latestGaji(): HasOne
    {
        return $this->hasOne(RefGajiAsn::class, 'peraturan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->orderByDesc('effective_date');
    }
}
