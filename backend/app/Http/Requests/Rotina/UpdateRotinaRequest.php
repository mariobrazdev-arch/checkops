<?php

namespace App\Http\Requests\Rotina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRotinaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo'             => ['sometimes', 'string', 'max:255'],
            'descricao'          => ['sometimes', 'nullable', 'string'],
            'setor_id'           => ['sometimes', 'uuid', 'exists:setores,id'],
            'frequencia'         => ['sometimes', 'in:diaria,semanal,mensal,turno'],
            'dias_semana'        => ['sometimes', 'nullable', 'array'],
            'dias_semana.*'      => ['integer', 'between:0,6'],
            'dias_mes'           => ['sometimes', 'nullable', 'array'],
            'dias_mes.*'         => ['integer', 'between:1,31'],
            'horario_previsto'   => ['sometimes', 'date_format:H:i'],
            'foto_obrigatoria'   => ['sometimes', 'boolean'],
            'so_camera'          => ['sometimes', 'boolean'],
            'justif_obrigatoria' => ['sometimes', 'boolean'],
            'status'             => ['sometimes', 'in:ativa,inativa'],
            'data_inicio'        => ['sometimes', 'date'],
            'data_fim'           => ['sometimes', 'nullable', 'date', 'after:data_inicio'],
            'colaborador_ids'    => ['sometimes', 'nullable', 'array'],
            'colaborador_ids.*'  => ['uuid', 'exists:users,id'],
        ];
    }
}
