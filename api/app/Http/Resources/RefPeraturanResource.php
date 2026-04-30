<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefPeraturanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jenis' => $this->jenis,
            'nomor' => $this->nomor,
            'tahun' => $this->tahun,
            'nama' => $this->nama,
            'effective_date' => $this->effective_date?->format('Y-m-d'),
        ];
    }
}