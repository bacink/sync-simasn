<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimAsnDownloadHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'nip',
        'nama',
        'jenis_dokumen',
        'file_name',
        'file_path',
        'status',
        'attempt_count',
        'last_attempt_at',
        'error_message',
        'file_hash',
    ];

    protected $table = 'sim_asn_download_histories';

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'last_attempt_at' => 'datetime',
        ];
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeByJenis(Builder $query, string $jenis): Builder
    {
        return $query->where('jenis_dokumen', $jenis);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function markSuccess(string $fileHash): void
    {
        $this->update([
            'status' => 'success',
            'attempt_count' => $this->attempt_count + 1,
            'last_attempt_at' => now(),
            'file_hash' => $fileHash,
            'error_message' => null,
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'attempt_count' => $this->attempt_count + 1,
            'last_attempt_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    public function resetForRetry(): void
    {
        $this->update(['status' => 'pending']);
    }
}
