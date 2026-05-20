<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

/**
 * Reusable helpers for SIM-ASN archive sync operations.
 */
final class SimAsnArchiveHelper
{
    /**
     * Base path under storage/app/private/ where archives are stored.
     */
    public const BASE_PATH = 'arsip';

    /**
     * Target disk for private archive storage.
     */
    public const DISK = 'local';

    /**
     * Chunk size for batch dispatching employee jobs.
     */
    public const CHUNK_SIZE = 50;

    /**
     * Default page size when fetching employees from SIM-ASN.
     */
    public const DEFAULT_PAGE_SIZE = 100;

    /**
     * Map document jenis to a display label used in logs/UI.
     */
    private const JENIS_LABELS = [
        'ijazah' => 'Ijazah',
        'sk_golongan' => 'SK Golongan',
        'sk_jabatan' => 'SK Jabatan',
        'sk_diklat' => 'SK Diklat',
        'sk_sertifikasi' => 'SK Sertifikasi',
        'skp' => 'SKP',
        'akta_kelahiran' => 'Akta Kelahiran',
        'akta_kematian' => 'Akta Kematian',
        'akta_cerai' => 'Akta Cerai',
        'akta_nikah' => 'Akta Nikah',
        'buku_nikah' => 'Buku Nikah',
        'surat_dokter' => 'Surat Dokter',
        'sk_sttpl' => 'SK STTPL',
        'sk_spmt' => 'SK SPMT',
        'kk' => 'Kartu Keluarga',
        'ktp' => 'KTP',
    ];

    /**
     * Return a human-readable label for a dokumen jenis.
     */
    public static function jenisLabel(string $jenis): string
    {
        return self::JENIS_LABELS[$jenis] ?? ucfirst(str_replace('_', ' ', $jenis));
    }

    /**
     * Build the relative storage path for a document.
     * Path: arsip/{jenis_dokumen}/{filename}
     * Note: NIP is already embedded in the filename itself.
     */
    public static function storagePath(string $jenisDokumen, string $filename): string
    {
        return self::BASE_PATH.'/'.$jenisDokumen.'/'.$filename;
    }

    /**
     * Check whether a file already exists in storage.
     */
    public static function fileExists(string $relativePath): bool
    {
        return Storage::disk(self::DISK)->exists($relativePath);
    }

    /**
     * Extract file extension from a file_type string or URL.
     */
    public static function resolveExtension(string $fileType, string $url): string
    {
        return match (strtolower($fileType)) {
            'pdf' => 'pdf',
            'png' => 'png',
            'image' => self::inferImageExtension($url),
            default => 'pdf',
        };
    }

    private static function inferImageExtension(string $url): string
    {
        if (preg_match('/\.(\w+)(?:\?|$)/', $url, $m)) {
            $ext = strtolower($m[1]);

            return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $ext : 'jpg';
        }

        return 'jpg';
    }

    /**
     * Map an array of document jenis strings to a comma-separated display string.
     */
    public static function formatJenisList(array $jenisList): string
    {
        if (empty($jenisList)) {
            return 'all';
        }

        $labels = array_map(fn ($j) => self::jenisLabel($j), $jenisList);

        return implode(', ', $labels);
    }
}
