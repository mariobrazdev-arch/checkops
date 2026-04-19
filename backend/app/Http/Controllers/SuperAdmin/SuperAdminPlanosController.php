<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plano;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminPlanosController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Plano::withCount('empresas')->orderBy('nome')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome'             => 'required|string|max:255',
            'limite_usuarios'  => 'required|integer|min:1',
            'limite_setores'   => 'required|integer|min:1',
            'limite_rotinas'   => 'required|integer|min:1',
            'ativo'            => 'sometimes|boolean',
        ]);

        $plano = Plano::create($dados);

        return response()->json(['data' => $plano, 'message' => 'Plano criado'], 201);
    }

    public function show(Plano $plano): JsonResponse
    {
        return response()->json(['data' => $plano->loadCount('empresas')]);
    }

    public function update(Request $request, Plano $plano): JsonResponse
    {
        $dados = $request->validate([
            'nome'             => 'sometimes|string|max:255',
            'limite_usuarios'  => 'sometimes|integer|min:1',
            'limite_setores'   => 'sometimes|integer|min:1',
            'limite_rotinas'   => 'sometimes|integer|min:1',
            'ativo'            => 'sometimes|boolean',
        ]);

        $plano->update($dados);

        return response()->json(['data' => $plano, 'message' => 'Plano atualizado']);
    }

    public function destroy(Plano $plano): JsonResponse
    {
        $plano->delete();

        return response()->json(['message' => 'Plano removido']);
    }
}
