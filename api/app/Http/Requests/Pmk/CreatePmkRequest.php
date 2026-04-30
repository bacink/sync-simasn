<?php

namespace App\Http\Requests\Pmk;

use Illuminate\Foundation\Http\FormRequest;

class CreatePmkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pegawai_id' => 'required|integer|min:1',
            'masa_kerja_lama_tahun' => 'required|integer|min:0|max:40',
            'masa_kerja_lama_bulan' => 'nullable|integer|min:0|max:11',
            'masa_kerja_baru_tahun' => 'required|integer|min:0|max:40',
            'masa_kerja_baru_bulan' => 'nullable|integer|min:0|max:11',
            'dasar_pmk' => 'nullable|string|max:500',
            'nomor_sk' => 'nullable|string|max:100',
            'tanggal_sk' => 'nullable|date',
            'file_id' => 'nullable|integer|exists:files,id',
        ];
    }

    public function messages(): array
    {
        return [
            'pegawai_id.required' => 'Pegawai ID wajib diisi',
            'masa_kerja_lama_tahun.required' => 'Masa kerja lama (tahun) wajib diisi',
            'masa_kerja_baru_tahun.required' => 'Masa kerja baru (tahun) wajib diisi',
        ];
    }
}