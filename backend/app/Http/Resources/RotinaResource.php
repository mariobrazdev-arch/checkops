<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RotinaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'empresa_id'         => $this->empresa_id,
            'setor_id'           => $this->setor_id,
            'setor'              => $this->whenLoaded('setor', fn () => [
                'id'   => $this->setor->id,
                'nome' => $this->setor->nome,
            ]),
            'titulo'             => $this->titulo,
            'descricao'          => $this->descricao,
            'frequencia'         => $this->frequencia,
            'dias_semana'        => $this->dias_semana,
            'dias_mes'           => $this->dias_mes,
            'horario_previsto'   => substr($this->horario_previsto, 0, 5),
            'foto_obrigatoria'   => $this->foto_obrigatoria,
            'so_camera'          => $this->so_camera,
            'justif_obrigatoria' => $this->justif_obrigatoria,
            'status'             => $this->status,
            'data_inicio'        => $this->data_inicio?->toDateString(),
            'data_fim'           => $this->data_fim?->toDateString(),
            'colaborador_ids'    => $this->whenLoaded('colaboradores', fn () =>
                $this->colaboradores->pluck('id')->all()
            ),
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }
}
