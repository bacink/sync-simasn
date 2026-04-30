<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class File extends Model
{
    protected $table = 'files';

    protected $fillable = [
        'path',
        'name',
        'original_name',
        'mime',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /** Polymorphic: this file can belong to multiple record types */
    public function fileables(): MorphMany
    {
        return $this->morphMany(Fileable::class, 'file');
    }

    /** Convenience: attach this file to any model */
    public function attachTo(object $model, ?string $kategori = null): Fileable
    {
        return $this->fileables()->create([
            'fileable_type' => get_class($model),
            'fileable_id' => $model->getKey(),
            'kategori' => $kategori,
        ]);
    }
}
