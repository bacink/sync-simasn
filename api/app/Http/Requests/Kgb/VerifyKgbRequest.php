<?php

namespace App\Http\Requests\Kgb;

use Illuminate\Foundation\Http\FormRequest;

class VerifyKgbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('kgb verify');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:approve,reject'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Aksi wajib diisi',
            'action.in' => 'Aksi harus berupa approve atau reject',
        ];
    }
}