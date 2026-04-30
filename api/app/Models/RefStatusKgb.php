<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefStatusKgb extends Model
{
    protected $table = 'ref_status_kgb';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'urutan',
        'is_final',
        'allow_edit',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'allow_edit' => 'boolean',
        'urutan' => 'integer',
    ];
}