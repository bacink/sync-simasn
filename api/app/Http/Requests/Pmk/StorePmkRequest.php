<?php

namespace App\Http\Requests\Pmk;

use Illuminate\Foundation\Http\FormRequest;

class StorePmkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pegawai_id' => ['required', 'string'],
            'nip' => ['required', 'string', 'max:255'],
            'nama' => ['required', 'string', 'max:255'],
            'masa_kerja_lama_tahun' => ['required', 'integer', 'min:0'],
            'masa_kerja_lama_bulan' => ['required', 'integer', 'min:0', 'max:11'],
            'masa_kerja_baru_tahun' => ['required', 'integer', 'min:0'],
            'masa_kerja_baru_bulan' => ['required', 'integer', 'min:0', 'max:11'],
            'dasar_pmk' => ['nullable', 'string'],
            'nomor_sk' => ['required', 'string', 'max:255'],
            'tanggal_sk' => ['required', 'date'],
            'file_sk' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
