<?php

namespace App\Http\Requests\Rotina;

use Illuminate\Foundation\Http\FormRequest;

class StoreRotinaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorização via Policy no controller
    }

    public function rules(): array
    {
        $isGestor = $this->user()?->perfil === 'gestor';

        return [
            'titulo'             => ['required', 'string', 'max:255'],
            'descricao'          => ['nullable', 'string'],
            'setor_id'           => $isGestor ? ['nullable'] : ['required', 'uuid', 'exists:setores,id'],
            'frequencia'         => ['required', 'in:diaria,semanal,mensal,turno'],
            'dias_semana'        => ['required_if:frequencia,semanal', 'nullable', 'array'],
            'dias_semana.*'      => ['integer', 'between:0,6'],
            'dias_mes'           => ['required_if:frequencia,mensal', 'nullable', 'array'],
            'dias_mes.*'         => ['integer', 'between:1,31'],
            'horario_previsto'   => ['required', 'date_format:H:i'],
            'foto_obrigatoria'   => ['boolean'],
            'so_camera'          => ['boolean'],
            'justif_obrigatoria' => ['boolean'],
            'data_inicio'        => ['required', 'date'],
            'data_fim'           => ['nullable', 'date', 'after:data_inicio'],
            'colaborador_ids'    => ['nullable', 'array'],
            'colaborador_ids.*'  => ['uuid', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'setor_id.exists'           => 'Setor não encontrado.',
            'frequencia.in'             => 'Frequência inválida. Use: diaria, semanal, mensal ou turno.',
            'dias_semana.required_if'   => 'Dias da semana são obrigatórios para frequência semanal.',
            'dias_mes.required_if'      => 'Dias do mês são obrigatórios para frequência mensal.',
            'horario_previsto.date_format' => 'Horário deve estar no formato HH:MM.',
            'data_fim.after'            => 'Data de fim deve ser após a data de início.',
        ];
    }
}
