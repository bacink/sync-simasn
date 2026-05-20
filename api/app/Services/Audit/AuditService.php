<?php

namespace App\Services\Audit;

use App\Models\KgbAuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function log(
        string $module,
        string $action,
        mixed $targetId = null,
        ?string $targetType = null,
        ?array $oldData = null,
        ?array $newData = null,
        ?string $reason = null
    ): KgbAuditLog {
        $user = Auth::user();

        return KgbAuditLog::create([
            'module' => $module,
            'action' => $action,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'target_id' => $targetId,
            'target_type' => $targetType,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    public function logKgb(string $action, $kgb, ?array $oldData = null, ?string $reason = null): KgbAuditLog
    {
        return $this->log(
            module: 'KGB',
            action: $action,
            targetId: $kgb->id,
            targetType: 'RiwayatKgb',
            oldData: $oldData,
            newData: $kgb->toArray(),
            reason: $reason
        );
    }

    public function logPmk(string $action, $pmk, ?array $oldData = null, ?string $reason = null): KgbAuditLog
    {
        return $this->log(
            module: 'PMK',
            action: $action,
            targetId: $pmk->id,
            targetType: 'RiwayatPmk',
            oldData: $oldData,
            newData: $pmk->toArray(),
            reason: $reason
        );
    }
}
