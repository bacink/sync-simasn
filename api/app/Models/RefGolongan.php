<?php

namespace App\Models;

use App\Enums\JenisAsn;
use Illuminate\Database\Eloquent\Model;

class RefGolongan extends Model
{
    protected $table = 'ref_golongan';

    protected $fillable = [
        'jenis_asn',
        'golongan',
        'sub_golongan',
        'pangkat',
        'urutan',
    ];

    protected $casts = [
        'jenis_asn' => JenisAsn::class,
    ];

    /**
     * Get the full rank name (e.g., "IV/a")
     */
    public function getLabelAttribute(): string
    {
        return $this->sub_golongan 
            ? "{$this->golongan}/{$this->sub_golongan}" 
            : $this->golongan;
    }
}
