<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefGolonganResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jenis_asn' => $this->jenis_asn->value,
            'golongan' => $this->golongan,
            'sub_golongan' => $this->sub_golongan,
            'pangkat' => $this->pangkat,
            'urutan' => $this->urutan,
        ];
    }
}