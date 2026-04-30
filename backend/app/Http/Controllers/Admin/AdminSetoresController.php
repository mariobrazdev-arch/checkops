<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setor;
use App\Traits\ResolvesEmpresaId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSetoresController extends Controller
{
    use ResolvesEmpresaId;

    public function index(Request $request): JsonResponse
    {
        $setores = Setor::where('empresa_id', $this->resolveEmpresaId($request))
            ->with('gestor:id,nome,email')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('nome')
            ->get();

        return response()->json(['data' => $setores]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome'      => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'gestor_id' => 'nullable|uuid|exists:users,id',
            'status'    => 'sometimes|in:ativo,inativo',
        ]);

        $dados['empresa_id'] = $this->resolveEmpresaId($request);
        $dados['status'] = $dados['status'] ?? 'ativo';

        $setor = Setor::create($dados);
        $setor->load('gestor:id,nome,email');

        return response()->json(['data' => $setor, 'message' => 'Setor criado'], 201);
    }

    public function show(Setor $setor): JsonResponse
    {
        return response()->json(['data' => $setor->load('gestor:id,nome,email')]);
    }

    public function update(Request $request, Setor $setor): JsonResponse
    {
        $dados = $request->validate([
            'nome'      => 'sometimes|string|max:255',
            'descricao' => 'sometimes|nullable|string',
            'gestor_id' => 'sometimes|nullable|uuid|exists:users,id',
            'status'    => 'sometimes|in:ativo,inativo',
        ]);

        $setor->update($dados);

        return response()->json(['data' => $setor->load('gestor:id,nome,email'), 'message' => 'Setor atualizado']);
    }

    public function destroy(Setor $setor): JsonResponse
    {
        $setor->delete();

        return response()->json(['message' => 'Setor removido']);
    }
}
