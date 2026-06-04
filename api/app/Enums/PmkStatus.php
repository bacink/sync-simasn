<?php

namespace App\Enums;

/**
 * Enum representing the workflow status of a PMK (Peninjauan Masa Kerja) record.
 * Values are stored as lowercase strings in the database ('draft', 'aktif', 'nonaktif').
 */
enum PmkStatus: string
{
    case DRAFT = 'draft';
    case AKTIF = 'aktif';
    case NONAKTIF = 'nonaktif';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::AKTIF => 'Aktif',
            self::NONAKTIF => 'Nonaktif',
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
