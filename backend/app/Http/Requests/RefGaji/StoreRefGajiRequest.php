<?php

namespace App\Http\Requests\RefGaji;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefGajiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ref_gaji manage');
    }

    public function rules(): array
    {
        return [
            'golongan'          => ['required', 'regex:/^(IV|X)/i'],
            'masa_kerja_tahun' => ['required', 'integer'],
            'gaji'              => ['required', 'numeric'],
        ];
    }
}