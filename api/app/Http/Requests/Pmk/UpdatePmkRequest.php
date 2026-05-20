<?php

namespace App\Http\Requests\Pmk;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePmkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pmk = $this->route('pmk');
        return $pmk && $pmk->status->value === 'draft' && $this->user()->can('pmk edit');
    }

    public function rules(): array
    {
        return [
            'no_sk'                  => ['sometimes', 'string', 'max:100'],
            'tanggal_sk'             => ['sometimes', 'date', 'date_format:Y-m-d'],
            'masa_kerja_lama_tahun'  => ['sometimes', 'integer'],
            'masa_kerja_lama_bulan'  => ['sometimes', 'integer'],
            'masa_kerja_baru_tahun'  => ['sometimes', 'integer'],
            'masa_kerja_baru_bulan'  => ['sometimes', 'integer'],
        ];
    }
}