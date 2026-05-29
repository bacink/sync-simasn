<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterFromSimAsnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sim_asn_user_id' => ['required', 'string', 'max:255', 'unique:users,sim_asn_user_id'],
            'sim_asn_token' => ['required', 'array'],
            'sim_asn_token.access_token' => ['required', 'string'],
            'sim_asn_token.refresh_token' => ['nullable', 'string'],
            'sim_asn_token.expires_at' => ['nullable', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'opd_id' => ['nullable', 'integer', 'exists:opds,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'sim_asn_user_id.unique' => 'Akun SIM-ASN ini sudah terdaftar.',
            'email.unique' => 'Email ini sudah terdaftar di sistem.',
        ];
    }
}
