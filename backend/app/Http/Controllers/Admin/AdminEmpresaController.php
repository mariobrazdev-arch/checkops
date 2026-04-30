<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Traits\ResolvesEmpresaId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminEmpresaController extends Controller
{
    use ResolvesEmpresaId;

    public function index(): JsonResponse { return response()->json(['message' => 'Not implemented'], 501); }

    public function show(Request $request): JsonResponse
    {
        $empresa = Empresa::findOrFail($this->resolveEmpresaId($request));
        return response()->json(['data' => $empresa]);
    }

    public function update(Request $request): JsonResponse
    {
        $empresa = Empresa::findOrFail($this->resolveEmpresaId($request));

        $dados = $request->validate([
            'nome'        => 'sometimes|string|max:255',
            'telefone'    => 'sometimes|nullable|string|max:20',
            'email'       => 'sometimes|email|max:255',
            'responsavel' => 'sometimes|nullable|string|max:255',
            'cep'         => 'sometimes|nullable|string|max:9',
            'logradouro'  => 'sometimes|nullable|string|max:255',
            'numero'      => 'sometimes|nullable|string|max:20',
            'complemento' => 'sometimes|nullable|string|max:255',
            'bairro'      => 'sometimes|nullable|string|max:255',
            'cidade'      => 'sometimes|nullable|string|max:255',
            'estado'      => 'sometimes|nullable|string|size:2',
        ]);

        $empresa->update($dados);

        return response()->json(['data' => $empresa, 'message' => 'Empresa atualizada']);
    }
}
