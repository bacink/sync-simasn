<?php

namespace App\Enums;

/**
 * Enum representing the workflow status of a KGB record.
 */
enum KgbStatus: string
{
    case Draft = 'draft';
    case Diajukan = 'diajukan';
    case Diverifikasi = 'diverifikasi';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
}
