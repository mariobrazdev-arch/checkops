<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminEmpresaController extends Controller
{
    public function index(): JsonResponse { return response()->json(['message' => 'Not implemented'], 501); }

    public function show(Request $request): JsonResponse
    {
        $empresa = Empresa::findOrFail($request->user()->empresa_id);
        return response()->json(['data' => $empresa]);
    }

    public function update(Request $request): JsonResponse
    {
        $empresa = Empresa::findOrFail($request->user()->empresa_id);

        $dados = $request->validate([
            'nome'        => 'sometimes|string|max:255',
            'telefone'    => 'sometimes|nullable|string|max:20',
            'email'       => 'sometimes|email|max:255',
            'responsavel' => 'sometimes|nullable|string|max:255',
        ]);

        $empresa->update($dados);

        return response()->json(['data' => $empresa, 'message' => 'Empresa atualizada']);
    }
}
