<?php

namespace App\Http\Requests\Kgb;

use Illuminate\Foundation\Http\FormRequest;

class GenerateKgbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pegawai_id' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'pegawai_id.required' => 'Pegawai ID wajib diisi',
            'pegawai_id.integer' => 'Pegawai ID harus berupa angka',
            'pegawai_id.min' => 'Pegawai ID tidak valid',
        ];
    }
}