<?php

namespace App\Enums;

enum KgbStatus: string
{
    case DRAFT = 'draft';
    case DIAJUKAN = 'diajukan';
    case DIVERIFIKASI = 'divverifikasi';
    case DISETUJUI = 'disetujui';
    case DITOLAK = 'ditolak';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::DIAJUKAN => 'Diajukan',
            self::DIVERIFIKASI => 'Diverifikasi',
            self::DISETUJUI => 'Disetujui',
            self::DITOLAK => 'Ditolak',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match($this) {
            self::DRAFT => $next === self::DIAJUKAN || $next === self::DITOLAK,
            self::DIAJUKAN => $next === self::DIVERIFIKASI || $next === self::DITOLAK,
            self::DIVERIFIKASI => $next === self::DISETUJUI || $next === self::DITOLAK,
            self::DISETUJUI, self::DITOLAK => false,
        };
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    public function isDeletable(): bool
    {
        return $this === self::DRAFT;
    }
}
