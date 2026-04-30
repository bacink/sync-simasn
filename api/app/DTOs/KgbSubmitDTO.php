<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

class KgbSubmitDTO
{
    public function __construct(
        public readonly string $nomorSk,
        public readonly string $tanggalSk,
        public readonly ?UploadedFile $fileSk = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            nomorSk: $data['nomor_sk'],
            tanggalSk: $data['tanggal_sk'],
            fileSk: $data['file_sk'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'nomor_sk' => $this->nomorSk,
            'tanggal_sk' => $this->tanggalSk,
            'file_sk' => $this->fileSk,
        ];
    }
}
