<?php

namespace App\Http\Requests\Kgb;

use Illuminate\Foundation\Http\FormRequest;

class KgbSubmitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization logic can be handled here or via Policies
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
            'nomor_sk' => ['required', 'string', 'max:255'],
            'tanggal_sk' => ['required', 'date'],
            'file_sk' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
