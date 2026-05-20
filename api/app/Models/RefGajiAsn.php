<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefGajiAsn extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ref_gaji_asn';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'golongan',
        'masa_kerja_tahun',
        'gaji',
        'peraturan',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gaji' => 'decimal:0',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Find a record by golongan and masa kerja tahun.
     */
    public static function findByGolonganAndMasaKerja(string $golongan, int $masaKerjaTahun): ?self
    {
        return static::active()
            ->where('golongan', $golongan)
            ->where('masa_kerja_tahun', $masaKerjaTahun)
            ->first();
    }
}