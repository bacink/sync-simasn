<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KgbApproval extends Model
{
    use SoftDeletes;

    protected $table = 'kgb_approvals';

    protected $fillable = [
        'riwayat_kgb_id',
        'user_id',
        'role',
        'step_order',
        'is_required',
        'catatan',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'is_required' => 'boolean',
    ];

    public function riwayatKgb(): BelongsTo
    {
        return $this->belongsTo(RiwayatKgb::class, 'riwayat_kgb_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
