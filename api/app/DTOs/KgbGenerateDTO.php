<?php

namespace App\DTOs;

use InvalidArgumentException;

class KgbGenerateDTO
{
    public function __construct(
        public readonly int $pegawaiId,
    ) {
        if ($pegawaiId <= 0) {
            throw new InvalidArgumentException('Pegawai ID harus valid');
        }
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            pegawaiId: (string) $data['pegawai_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'pegawai_id' => $this->pegawaiId,
        ];
    }
}
