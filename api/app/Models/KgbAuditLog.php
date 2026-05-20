<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KgbAuditLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kgb_audit_logs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'module',
        'action',
        'user_id',
        'user_name',
        'target_id',
        'target_type',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
        'reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_data' => 'array',
            'new_data' => 'array',
            'created_at' => 'datetime',
        ];
    }
}