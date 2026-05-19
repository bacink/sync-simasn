<?php

namespace App\Services\Kgb;

use App\Models\RiwayatKgb;
use Illuminate\Support\Facades\Storage;

class KgbDocumentService
{
    public function generateSkPath(RiwayatKgb $kgb): string
    {
        $dir = config('kgb.storage.sk_path', 'sk-kgb');
        $year = date('Y');
        return "{$dir}/{$year}/SK-{$kgb->id}-{$kgb->pegawai_nip}.pdf";
    }

    public function storeSk(RiwayatKgb $kgb, string $base64Content): string
    {
        $path = $this->generateSkPath($kgb);
        $content = base64_decode($base64Content);

        Storage::disk(config('kgb.storage.disk', 's3'))
            ->put($path, $content);

        return $path;
    }

    public function getSkUrl(RiwayatKgb $kgb): ?string
    {
        if (!$kgb->file_sk_id) return null;

        $disk = Storage::disk(config('kgb.storage.disk', 's3'));
        $path = $this->generateSkPath($kgb);

        if (!$disk->exists($path)) return null;

        return $disk->url($path);
    }
}
