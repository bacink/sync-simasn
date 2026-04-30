<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PmkResource extends JsonResource
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
            'masa_kerja_lama' => [
                'tahun' => $this->masa_kerja_lama_tahun,
                'bulan' => $this->masa_kerja_lama_bulan,
            ],
            'masa_kerja_baru' => [
                'tahun' => $this->masa_kerja_baru_tahun,
                'bulan' => $this->masa_kerja_baru_bulan,
            ],
            'dasar_pmk' => $this->dasar_pmk,
            'nomor_sk' => $this->nomor_sk,
            'tanggal_sk' => $this->tanggal_sk?->format('Y-m-d'),
            'file_id' => $this->file_id,
            'file' => $this->whenLoaded('file'),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
