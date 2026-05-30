<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterFromSimAsnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'opd_id' => ['required', 'integer', 'exists:opds,id'],
            'sim_asn_user_id' => ['required', 'string', 'uuid', Rule::unique('users', 'sim_asn_user_id')],
            'sim_asn_token' => ['required', 'array'],
            'sim_asn_token.access_token' => ['required', 'string'],
            'sim_asn_token.refresh_token' => ['nullable', 'string'],
            'sim_asn_token.expires_at' => ['nullable', 'string'],
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
