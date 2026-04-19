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
            'foto_base64'    => ['nullable', 'string'],
            'foto_timestamp' => ['nullable', 'integer'],
            'foto_device_id' => ['nullable', 'string', 'max:255'],
            'foto_lat'       => ['nullable', 'numeric', 'between:-90,90'],
            'foto_lng'       => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
