<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'acao'        => $this->acao,
            'entidade'    => $this->entidade,
            'entidade_id' => $this->entidade_id,
            'dados_antes' => $this->dados_antes,
            'dados_depois'=> $this->dados_depois,
            'ip'          => $this->ip,
            'created_at'  => $this->created_at?->toIso8601String(),
            'usuario'     => $this->whenLoaded('usuario', fn () => [
                'id'   => $this->usuario->id,
                'nome' => $this->usuario->nome,
            ]),
        ];
    }
}
