<?php

namespace App\Http\Requests\Kgb;

use Illuminate\Foundation\Http\FormRequest;

class ApproveKgbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('kgb approve');
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}