<?php

namespace App\Http\Requests\Kgb;

use Illuminate\Foundation\Http\FormRequest;

class RejectKgbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('kgb approve');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}