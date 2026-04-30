<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefJenisKgb extends Model
{
    protected $table = 'ref_jenis_kgb';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
    ];
}