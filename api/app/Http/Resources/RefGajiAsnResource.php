<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefGajiAsnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jenis_asn' => $this->jenis_asn->value,
            'golongan' => $this->golongan,
            'sub_golongan' => $this->sub_golongan,
            'masa_kerja' => $this->masa_kerja,
            'gaji' => (int) $this->gaji,
            'peraturan' => new RefPeraturanResource($this->whenLoaded('peraturan')),
        ];
    }
}