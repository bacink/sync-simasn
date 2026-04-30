<?php

namespace App\Http\Requests\Kgb;

use Illuminate\Foundation\Http\FormRequest;

class KgbRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan' => 'required|string|max:1000',
        ];
    }
}
