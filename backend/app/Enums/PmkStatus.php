<?php

namespace App\Enums;

enum PmkStatus: string
{
    case DRAFT = 'draft';
    case AKTIF = 'aktif';
    case NONAKTIF = 'nonaktif';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::AKTIF => 'Aktif',
            self::NONAKTIF => 'Nonaktif',
        };
    }
}
