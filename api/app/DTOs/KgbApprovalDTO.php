<?php

namespace App\DTOs;

class KgbApprovalDTO
{
    public function __construct(
        public readonly ?string $catatan = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            catatan: $data['catatan'] ?? null,
        );
    }
}
