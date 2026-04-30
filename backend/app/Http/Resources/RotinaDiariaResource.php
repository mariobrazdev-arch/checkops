<?php

namespace App\Http\Resources;

use App\Services\FotoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RotinaDiariaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'rotina'             => $this->whenLoaded('rotina', fn () => [
                'id'                 => $this->rotina->id,
                'titulo'             => $this->rotina->titulo,
                'descricao'          => $this->rotina->descricao,
                'foto_obrigatoria'   => $this->rotina->foto_obrigatoria,
                'so_camera'          => $this->rotina->so_camera,
                'justif_obrigatoria' => $this->rotina->justif_obrigatoria,
                'horario_previsto'   => $this->rotina->horario_previsto,
                'setor'              => $this->rotina->relationLoaded('setor')
                    ? ['id' => $this->rotina->setor?->id, 'nome' => $this->rotina->setor?->nome]
                    : null,
            ]),
            'colaborador_id'     => $this->colaborador_id,
            'data'               => $this->data?->toDateString(),
            'status'             => $this->status,
            'data_hora_resposta' => $this->data_hora_resposta?->toIso8601String(),
            'justificativa'      => $this->justificativa,
            // RN-10: nunca retorna path bruto — sempre URL pública
            'foto_url'           => $this->foto_url
                ? app(FotoService::class)->urlTemporaria($this->foto_url)
                : null,
            'fotos'              => $this->whenLoaded('fotos', fn () =>
                $this->fotos->map(fn ($f) => [
                    'id'    => $f->id,
                    'url'   => app(FotoService::class)->urlTemporaria($f->path),
                    'ordem' => $f->ordem,
                ])
            ),
            'foto_lat'           => $this->foto_lat,
            'foto_lng'           => $this->foto_lng,
            'foto_timestamp'     => $this->foto_timestamp?->toIso8601String(),
            'foto_device_id'     => $this->foto_device_id,
            'reaberta_por'       => $this->reaberta_por,
            'reaberta_justificativa' => $this->reaberta_justificativa,
        ];
    }
}
