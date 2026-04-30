<?php

namespace App\Enums;

/**
 * Enum representing the type of KGB (Kenaikan Gaji Berkala).
 */
enum KgbType: string
{
    case Reguler = 'reguler';
    case Penyesuaian = 'penyesuaian';
}
