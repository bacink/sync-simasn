<?php

namespace App\Exceptions;

enum ApiErrorCode: string
{
    case AUTH_INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS';
    case AUTH_TOKEN_EXPIRED = 'AUTH_TOKEN_EXPIRED';
    case AUTH_TOKEN_INVALID = 'AUTH_TOKEN_INVALID';
    case AUTH_FORBIDDEN = 'AUTH_FORBIDDEN';
    case KGB_NOT_FOUND = 'KGB_NOT_FOUND';
    case KGB_ALREADY_SUBMITTED = 'KGB_ALREADY_SUBMITTED';
    case KGB_INVALID_TRANSITION = 'KGB_INVALID_TRANSITION';
    case KGB_CANNOT_MODIFY = 'KGB_CANNOT_MODIFY';
    case KGB_VALIDATION_FAILED = 'KGB_VALIDATION_FAILED';
    case KGB_PEGAWAI_NOT_FOUND = 'KGB_PEGAWAI_NOT_FOUND';
    case PMK_NOT_FOUND = 'PMK_NOT_FOUND';
    case PMK_VALIDATION_FAILED = 'PMK_VALIDATION_FAILED';
    case REF_GAJI_NOT_FOUND = 'REF_GAJI_NOT_FOUND';
    case SIMASN_CONNECTION_FAILED = 'SIMASN_CONNECTION_FAILED';
    case SIMASN_DATA_NOT_FOUND = 'SIMASN_DATA_NOT_FOUND';
    case SYSTEM_ERROR = 'SYSTEM_ERROR';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';

    public function httpStatus(): int
    {
        return match($this) {
            self::AUTH_INVALID_CREDENTIALS,
            self::AUTH_TOKEN_EXPIRED,
            self::AUTH_TOKEN_INVALID => 401,
            self::AUTH_FORBIDDEN => 403,
            self::KGB_NOT_FOUND,
            self::KGB_PEGAWAI_NOT_FOUND,
            self::PMK_NOT_FOUND,
            self::SIMASN_DATA_NOT_FOUND => 404,
            self::KGB_ALREADY_SUBMITTED,
            self::KGB_INVALID_TRANSITION,
            self::KGB_CANNOT_MODIFY => 409,
            self::KGB_VALIDATION_FAILED,
            self::PMK_VALIDATION_FAILED,
            self::REF_GAJI_NOT_FOUND => 422,
            self::SIMASN_CONNECTION_FAILED => 503,
            self::SYSTEM_ERROR,
            self::VALIDATION_ERROR => 500,
        };
    }

    public function defaultMessage(): string
    {
        return match($this) {
            self::AUTH_INVALID_CREDENTIALS => 'Email atau password salah',
            self::AUTH_TOKEN_EXPIRED => 'Token sudah expire, silakan login ulang',
            self::AUTH_TOKEN_INVALID => 'Token tidak valid',
            self::AUTH_FORBIDDEN => 'Anda tidak memiliki akses ke resource ini',
            self::KGB_NOT_FOUND => 'Data KGB tidak ditemukan',
            self::KGB_ALREADY_SUBMITTED => 'KGB sudah diajukan, tidak bisa diedit',
            self::KGB_INVALID_TRANSITION => 'Transisi status tidak valid',
            self::KGB_CANNOT_MODIFY => 'KGB sudah diverifikasi/disetujui, tidak bisa diubah',
            self::KGB_VALIDATION_FAILED => 'Data KGB tidak valid',
            self::KGB_PEGAWAI_NOT_FOUND => 'Data pegawai tidak ditemukan di SIM-ASN',
            self::PMK_NOT_FOUND => 'Data PMK tidak ditemukan',
            self::PMK_VALIDATION_FAILED => 'Data PMK tidak valid',
            self::REF_GAJI_NOT_FOUND => 'Referensi gaji tidak ditemukan',
            self::SIMASN_CONNECTION_FAILED => 'Gagal terhubung ke sistem SIM-ASN',
            self::SIMASN_DATA_NOT_FOUND => 'Data tidak ditemukan di SIM-ASN',
            self::SYSTEM_ERROR => 'Terjadi kesalahan sistem',
            self::VALIDATION_ERROR => 'Validasi request gagal',
        };
    }
}
