<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nome'       => $this->nome,
            'email'      => $this->email,
            'telefone'   => $this->telefone,
            'perfil'     => $this->perfil,
            'empresa_id' => $this->empresa_id,
            'setor_id'   => $this->setor_id,
            'setor_nome' => $this->whenLoaded('setor', fn() => $this->setor?->nome),
            'status'     => $this->status,
        ];
    }
}
