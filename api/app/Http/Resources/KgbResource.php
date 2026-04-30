<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KgbResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pegawai_id' => $this->pegawai_id,
            'nip' => $this->nip,
            'nama' => $this->nama,
            'golongan' => $this->golongan,
            'masa_kerja' => [
                'tahun' => $this->masa_kerja_tahun,
                'bulan' => $this->masa_kerja_bulan,
            ],
            'gaji_lama' => $this->gaji_lama,
            'gaji_baru' => $this->gaji_baru,
            'tmt_kgb' => $this->tmt_kgb?->format('Y-m-d'),
            'nomor_sk' => $this->nomor_sk,
            'tanggal_sk' => $this->tanggal_sk?->format('Y-m-d'),
            'status' => $this->status,
            'jenis_kgb' => $this->jenis_kgb,
            'snapshot' => $this->whenLoaded('snapshot'),
            'calculation' => $this->whenLoaded('calculation'),
            'pmk' => $this->whenLoaded('pmk'),
        ];
    }
}
