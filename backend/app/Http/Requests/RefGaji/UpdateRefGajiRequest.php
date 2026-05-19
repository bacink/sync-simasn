<?php

namespace App\Http\Requests\RefGaji;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRefGajiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ref_gaji manage');
    }

    public function rules(): array
    {
        return [
            'golongan'          => ['sometimes', 'regex:/^(IV|X)/i'],
            'masa_kerja_tahun' => ['sometimes', 'integer'],
            'gaji'              => ['sometimes', 'numeric'],
        ];
    }
}