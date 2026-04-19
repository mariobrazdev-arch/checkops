<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Extends RotinaDiariaResource com dados do colaborador e link de mapa.
 * Usado por gestor (validação) e colaborador (histórico).
 */
class RotinaDiariaDetalheResource extends RotinaDiariaResource
{
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);

        // Colaborador com dados de identificação
        $base['colaborador'] = $this->whenLoaded('colaborador', fn () => [
            'id'        => $this->colaborador->id,
            'nome'      => $this->colaborador->nome,
            'matricula' => $this->colaborador->matricula,
            'cargo'     => $this->colaborador->cargo,
        ]);

        // Mapa do Google Maps se GPS disponível
        $base['mapa_url'] = ($this->foto_lat && $this->foto_lng)
            ? "https://maps.google.com/?q={$this->foto_lat},{$this->foto_lng}"
            : null;

        return $base;
    }
}
