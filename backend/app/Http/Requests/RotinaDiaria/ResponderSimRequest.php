<?php

namespace App\Http\Requests\RotinaDiaria;

use Illuminate\Foundation\Http\FormRequest;

class ResponderSimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fotos_base64'   => ['nullable', 'array', 'max:5'],
            'fotos_base64.*' => ['required', 'string'],
            'foto_timestamp' => ['nullable', 'integer'],
            'foto_device_id' => ['nullable', 'string', 'max:255'],
            'foto_lat'       => ['nullable', 'numeric', 'between:-90,90'],
            'foto_lng'       => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
