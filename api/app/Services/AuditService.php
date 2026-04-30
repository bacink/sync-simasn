<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log an action to audit_logs.
     */
    public function log(string $tableName, int $recordId, string $action, ?array $oldData = null, ?array $newData = null): AuditLog
    {
        return AuditLog::create([
            'table_name' => $tableName,
            'record_id'  => $recordId,
            'action'     => $action,
            'old_data'   => $oldData,
            'new_data'   => $newData,
            'user_id'    => Auth::id(),
            'created_at' => now(),
        ]);
    }
}
