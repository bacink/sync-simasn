<?php

namespace App\Services\SimAsn;

use Illuminate\Support\Str;

readonly class ArchiveNamingMapper
{
    /**
     * Naming patterns per archive category.
     * Key = category slug
     * Value = ['pattern' => string]
     *
     * Placeholders: {NIP}, {KEY}, {LEVEL}, {SEQ}, {CODE}, {TRAINING_NAME}, {CONDITION_TYPE}
     */
    private const MAP = [
        'education'   => ['pattern' => '{NIP}_IJAZAH_{LEVEL}.pdf'],
        'golongan'    => ['pattern' => '{NIP}_SKKP_{SEQ}.pdf'],
        'jabatan'     => ['pattern' => '{NIP}_SKJABATAN_{SEQ}.pdf'],
        'training'    => ['pattern' => '{NIP}_SK_DIKLAT_{TRAINING_NAME}.pdf'],
        'sertifikasi' => ['pattern' => '{NIP}_SK_SERTIFIKASI_{TRAINING_NAME}.pdf'],
        'skp'         => ['pattern' => '{NIP}_SKP_{YEAR}.pdf'],
        'akta'        => ['pattern' => '{NIP}_AKTA_{CONDITION_TYPE}.pdf'],
        'berkas'      => ['pattern' => '{NIP}_{KEY}_{CONDITION_TYPE}.pdf'],
    ];

    /**
     * Maps SIM-ASN `jenis` field → category slug.
     * This maps the actual `jenis` values from the dokumen API response.
     */
    private const JENIS_MAP = [
        // Education (Ijazah)
        'ijazah'      => 'education',

        // Golongan (Rank/Salary Grade)
        'sk_golongan' => 'golongan',

        // Jabatan (Position)
        'sk_jabatan'  => 'jabatan',

        // Training / Diklat
        'sk_diklat'   => 'training',

        // Sertifikasi
        'sk_sertifikasi' => 'sertifikasi',

        // SKP (Performance Target)
        'skp'         => 'skp',

        // Civil documents (Akta, Buku Nikah, etc.)
        'akta_kelahiran'  => 'akta',
        'akta_kematian'   => 'akta',
        'akta_cerai'       => 'akta',
        'akta_nikah'       => 'akta',
        'buku_nikah'      => 'akta',

        // Other Berkas (fallback for everything else)
        'surat_dokter'        => 'berkas',
        'sk_sttpl'           => 'berkas',
        'sk_spmt'           => 'berkas',
        'sk_rekomendasi_inovasi' => 'berkas',
        'berita_acara'       => 'berkas',
        'pelantikan'         => 'berkas',
        'kk'                => 'berkas',
        'ktp'               => 'berkas',
    ];

    /**
     * Resolve the category slug for a given `jenis` value from SIM-ASN.
     * Falls back to 'berkas' if jenis is not mapped.
     */
    public static function getCategory(string $jenis): string
    {
        return self::JENIS_MAP[$jenis] ?? 'berkas';
    }

    /**
     * Get the naming pattern for a category.
     */
    public static function getPattern(string $category): string
    {
        return self::MAP[$category]['pattern'] ?? '{NIP}_{KEY}.pdf';
    }

    /**
     * Extract year from label (e.g., "SKP Tahun 2022" → "2022").
     */
    public static function extractYear(string $label): string
    {
        if (preg_match('/(\d{4})/', $label, $m)) {
            return $m[1];
        }
        return date('Y');
    }

    
    /**
     * Normalize education level from ijazah label.
     * Handles typos, synonyms, and full names from SIM-ASN.
     *
     * Key: normalized suffix, Value: array of regex patterns (checked in order)
     */
    private const EDUCATION_LEVELS = [
        'S1' => [
            '/\b(sarjana|s1|strata\s*1|gelar\s*s1)\b/i',
        ],
        'S2' => [
            '/\b(magister|megister|sp-1|sp1|s2|strata\s*2|gelar\s*s2|spesialis\s*1)\b/i',
        ],
        'S3' => [
            '/\b(doktor|dr\.|s3|strata\s*3|gelar\s*s3|spesialis\s*2|sp-2|sp2)\b/i',
        ],
        'D1' => [
            '/\b(diploma\s*1|d1)\b/i',
        ],
        'D2' => [
            '/\b(diploma\s*2|d2)\b/i',
        ],
        'D3' => [
            '/\b(diploma\s*3|d3)\b/i',
        ],
        'SMA' => [
            '/\b(sma|smk|ma|sekolah\s*menengah\s*atas|kejuruan|ipa|ips|sma\s*ipa|sma\s*ips|smk\s*\w+)\b/i',
        ],
        'SMP' => [
            '/\b(smp|mts|mt?s|sekolah\s*menengah\s*pertama)\b/i',
        ],
        'SD' => [
            '/\b(\bsd\b|mi\b|sekolah\s*dasar)\b/i',
        ],
        'PGSD' => [
            '/\b(pgsd|pendidikan\s*guru\s*sekolah\s*dasar)\b/i',
        ],
        'PGPAUD' => [
            '/\b(pgpaud|pendidikan\s*guru\s*paud)\b/i',
        ],
        'PAUD' => [
            '/\b(paud|pendidikan\s*anak\s*usia\s*din?\s*)\b/i',
        ],
    ];

    /**
     * Extract level from label for ijazah (e.g., "Ijazah SD", "Ijazah Sarjana").
     */
    public static function extractLevel(string $label): string
    {
        foreach (self::EDUCATION_LEVELS as $level => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $label)) {
                    return $level;
                }
            }
        }
        // Fallback: slugify if no pattern matched
        return self::slugify($label);
    }

    /**
     * Normalizegolongan/pangkat sequence from label.
     * Maps roman numerals + letter grade to normalized SKKP suffix.
     * Examples:
     *   "SK Golongan I/a"  → "11"
     *   "SK Golongan II/b" → "21"
     *   "SK Golongan III/a" → "31"
     *   "SK Golongan III/b" → "32"
     */
    private const GOLONGAN_SEQ_MAP = [
        // Golongan I (Juru)
        'i/a'  => '11', 'i/b'  => '12', 'i/c'  => '13', 'i/d'  => '14',
        // Golongan II (Pengatur)
        'ii/a' => '21', 'ii/b' => '22', 'ii/c' => '23', 'ii/d' => '24',
        // Golongan III (Penata)
        'iii/a' => '31', 'iii/b' => '32', 'iii/c' => '33', 'iii/d' => '34',
        // Golongan IV (Pembina)
        'iv/a' => '41', 'iv/b' => '42', 'iv/c' => '43', 'iv/d' => '44', 'iv/e' => '45',
    ];

    /**
     * Extract sequence number from label for jabatan/golongan.
     * Falls back to slugified label for unrecognized patterns.
     */
    public static function extractSeq(string $label): string
    {
        // Normalize: strip "SK Golongan ", uppercase, collapse spaces
        $normalized = preg_replace('/\s+/', '', strtoupper($label));
        $normalized = preg_replace('/^SK+GOLONGAN+/i', '', $normalized);

        // Direct lookup
        $lookupKey = strtolower($normalized);
        if (isset(self::GOLONGAN_SEQ_MAP[$lookupKey])) {
            return self::GOLONGAN_SEQ_MAP[$lookupKey];
        }

        // Try extracting roman numeral + slash + letter: "III/a" → "iii/a" → "31"
        if (preg_match('/^(I{1,3}|IV)\s*\/\s*([a-z])$/i', $lookupKey, $m)) {
            $roman = strtolower($m[1]);
            $letter = $m[2];
            $romanMap = ['i' => '1', 'ii' => '2', 'iii' => '3', 'iv' => '4'];
            if (isset($romanMap[$roman])) {
                $letterMap = ['a' => '1', 'b' => '2', 'c' => '3', 'd' => '4', 'e' => '5'];
                if (isset($letterMap[$letter])) {
                    return $romanMap[$roman] . $letterMap[$letter];
                }
            }
        }

        return self::slugify($label);
    }

    /**
     * Extract person name from label forakta (e.g., "akta_kelahiran Alifia" → "Alifia").
     */
    public static function extractConditionType(string $jenis, string $label): string
    {
        // Remove the jenis prefix from label if it starts with it
        $cleaned = preg_replace('/^' . preg_quote($jenis, '/') . '\s+/i', '', $label);
        // e.g., "akta_kelahiran Alifia Zahrotushita" → "Alifia_Zahrotushita"
        return self::slugify(trim($cleaned ?: $label));
    }

    /**
     * Generate the archive filename.
     *
     * @param string $nip      Employee NIP
     * @param string $jenis    Document jenis from SIM-ASN (e.g. 'ijazah', 'sk_golongan')
     * @param string $label     Document label (e.g. 'Ijazah Sekolah Dasar')
     * @param array  $meta     Additional metadata overrides
     * @return string          Formatted filename (e.g. '198501012001121001_IJAZAH_SD.pdf')
     */
    public static function generate(string $nip, string $jenis, string $label, array $meta = []): string
    {
        $category = self::getCategory($jenis);
        $pattern  = self::getPattern($category);

        $replacements = [
            '{NIP}'            => $nip,
            '{KEY}'            => $jenis,
            '{LEVEL}'          => $meta['level']          ?? self::extractLevel($label),
            '{SEQ}'            => $meta['sequence']       ?? self::extractSeq($label),
            '{CODE}'           => $meta['code']           ?? self::extractSeq($label),
            '{TRAINING_NAME}'  => ($meta['training_name'] ?? null)
                                    ? self::slugify($meta['training_name'])
                                    : self::slugify($label),
            '{CONDITION_TYPE}' => ($meta['condition_type'] ?? null)
                                    ? $meta['condition_type']
                                    : self::extractConditionType($jenis, $label),
            '{YEAR}'           => $meta['year']           ?? self::extractYear($label),
        ];

        $filename = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        // Sanitize: remove any character that is not alphanumeric, underscore, hyphen, or dot
        return (string) preg_replace('/[^A-Za-z0-9_.\-]/', '_', $filename);
    }

    /**
     * Slugify a string using Laravel's Str helper.
     */
    private static function slugify(string $value): string
    {
        return Str::slug($value, '_');
    }
}