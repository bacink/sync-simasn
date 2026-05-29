<?php

namespace App\Models;

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
        'masa_kerja',
        'gaji',
        'peraturan_id',
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
        ];
    }

    public function peraturan()
    {
        return $this->belongsTo(RefPeraturan::class, 'peraturan_id');
    }

    /**
     * Find a record by golongan and masa kerja tahun.
     */
    public static function findByGolonganAndMasaKerja(string $golongan, int $masaKerjaTahun): ?self
    {
        return static::active()
            ->where('golongan', $golongan)
            ->where('masa_kerja', $masaKerjaTahun)
            ->first();
    }
}
