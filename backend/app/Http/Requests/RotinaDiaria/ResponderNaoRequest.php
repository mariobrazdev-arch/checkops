<?php

namespace App\Http\Requests\RotinaDiaria;

use Illuminate\Foundation\Http\FormRequest;

class ResponderNaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'justificativa' => ['required', 'string', 'min:20', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'justificativa.required' => 'Justificativa é obrigatória.',
            'justificativa.min'      => 'Justificativa deve ter no mínimo 20 caracteres.',
        ];
    }
}
