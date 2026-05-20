<?php

namespace App\Http\Requests\Pmk;

use Illuminate\Foundation\Http\FormRequest;

class StorePmkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pmk create');
    }

    public function rules(): array
    {
        return [
            'pegawai_id'             => ['required', 'integer'],
            'no_sk'                  => ['required', 'string', 'max:100'],
            'tanggal_sk'             => ['required', 'date', 'date_format:Y-m-d'],
            'masa_kerja_lama_tahun'  => ['required', 'integer'],
            'masa_kerja_lama_bulan'  => ['required', 'integer'],
            'masa_kerja_baru_tahun'  => ['required', 'integer'],
            'masa_kerja_baru_bulan'  => ['required', 'integer'],
        ];
    }
}