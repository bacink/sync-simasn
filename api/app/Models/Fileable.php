<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Fileable extends Model
{
    protected $table = 'fileables';

    protected $fillable = [
        'file_id',
        'fileable_type',
        'fileable_id',
        'kategori',
    ];

    public function file(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }
}