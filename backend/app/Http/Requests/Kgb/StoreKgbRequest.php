<?php

namespace App\Http\Requests\Kgb;

use Illuminate\Foundation\Http\FormRequest;

class StoreKgbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('kgb create');
    }

    public function rules(): array
    {
        return [
            'pegawai_id' => ['required', 'integer', 'min:1'],
            'pmk_id' => ['nullable', 'integer', 'exists:riwayat_pmk,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'pegawai_id.required' => 'ID pegawai wajib diisi',
            'pegawai_id.integer' => 'ID pegawai harus berupa angka',
            'pmk_id.exists' => 'Data PMK tidak ditemukan',
        ];
    }
}