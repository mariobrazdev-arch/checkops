<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use App\Models\Setor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestorSetorController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $setor = Setor::with(['gestor:id,nome,email', 'colaboradores:id,nome,email,cargo,status,setor_id'])
            ->findOrFail($request->user()->setor_id);

        return response()->json(['data' => $setor]);
    }

    public function update(Request $request): JsonResponse
    {
        $setor = Setor::findOrFail($request->user()->setor_id);

        $dados = $request->validate([
            'nome'      => 'sometimes|string|max:255',
            'descricao' => 'sometimes|nullable|string|max:1000',
            'status'    => 'sometimes|in:ativo,inativo',
        ]);

        $setor->update($dados);

        return response()->json(['data' => $setor, 'message' => 'Setor atualizado']);
    }
}
