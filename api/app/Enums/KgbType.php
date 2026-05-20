<?php

namespace App\Enums;

/**
 * Enum representing the type of KGB (Kenaikan Gaji Berkala).
 */
enum KgbType: string
{
    case REGULER = 'reguler';
    case PENYESUAIAN = 'penyesuaian';

    public function label(): string
    {
        return match($this) {
            self::REGULER => 'Reguler',
            self::PENYESUAIAN => 'Penyesuaian',
        };
    }
}