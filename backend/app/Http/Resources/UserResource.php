<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'nome'            => $this->nome,
            'email'           => $this->email,
            'telefone'        => $this->telefone,
            'cpf'             => $this->cpf,
            'sexo'            => $this->sexo,
            'data_nascimento' => $this->data_nascimento?->format('Y-m-d'),
            'foto_perfil_url' => $this->foto_perfil_path
                ? Storage::disk('public')->url($this->foto_perfil_path)
                : null,
            'perfil'     => $this->perfil,
            'empresa_id' => $this->empresa_id,
            'setor_id'   => $this->setor_id,
            'setor_nome' => $this->whenLoaded('setor', fn() => $this->setor?->nome),
            'status'     => $this->status,
        ];
    }
}
