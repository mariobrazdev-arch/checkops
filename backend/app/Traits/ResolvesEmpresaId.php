<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait ResolvesEmpresaId
{
    protected function resolveEmpresaId(Request $request): string
    {
        if ($request->user()->perfil === 'super_admin') {
            $id = $request->input('empresa_id');
            abort_unless((bool) $id, 422, 'empresa_id obrigatório para super_admin');
            return $id;
        }

        return $request->user()->empresa_id;
    }
}
