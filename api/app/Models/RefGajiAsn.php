<?php

namespace App\Models;

use App\Enums\JenisAsn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable salary reference table.
 *
 * Rows must NEVER be updated — insert new rows for new regulations.
 * The unique constraint (jenis_asn, golongan, sub_golongan, masa_kerja, peraturan_id)
 * guarantees no duplicates per regulation version.
 */
class RefGajiAsn extends Model
{
    use HasFactory;

    protected $table = 'ref_gaji_asn';

    protected $fillable = [
        'jenis_asn',
        'peraturan_id',
        'golongan',
        'sub_golongan',
        'masa_kerja',
        'gaji',
    ];

    protected $hidden = [
        // No updates allowed — prevent accidental writes
    ];

    protected function casts(): array
    {
        return [
            'jenis_asn' => JenisAsn::class,
            'masa_kerja' => 'integer',
            'gaji' => 'decimal:2',
        ];
    }

    /**
     * Immutability guard: throw if anyone attempts to update a row.
     * Salary reference data is versioned — never modified in place.
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \RuntimeException(
                'ref_gaji_asn is immutable. Insert a new row for new regulation versions.'
            );
        }

        return parent::save($options);
    }

    public function delete(): ?bool
    {
        throw new \RuntimeException(
            'ref_gaji_asn rows must not be deleted. Historical salary data depends on them.'
        );
    }

    public function peraturan(): BelongsTo
    {
        return $this->belongsTo(RefPeraturan::class, 'peraturan_id');
    }
}
