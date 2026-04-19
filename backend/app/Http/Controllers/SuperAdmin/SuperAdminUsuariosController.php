<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperAdminUsuariosController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $usuarios = User::withoutGlobalScopes()
            ->with('empresa:id,nome')
            ->when($request->empresa_id, fn($q) => $q->where('empresa_id', $request->empresa_id))
            ->when($request->perfil, fn($q) => $q->where('perfil', $request->perfil))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('nome')
            ->get();

        return response()->json(['data' => $usuarios]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome'       => 'required|string|max:255',
            'email'      => 'required|email',
            'password'   => 'required|string|min:8',
            'perfil'     => 'required|in:super_admin,admin,gestor,colaborador',
            'empresa_id' => 'nullable|uuid|exists:empresas,id',
            'setor_id'   => 'nullable|uuid|exists:setores,id',
            'matricula'  => 'nullable|string|max:50',
            'cargo'      => 'nullable|string|max:100',
            'status'     => 'sometimes|in:ativo,inativo',
        ]);

        $dados['password'] = Hash::make($dados['password']);
        $dados['status'] = $dados['status'] ?? 'ativo';

        $usuario = User::withoutGlobalScopes()->create($dados);

        return response()->json(['data' => $usuario->load('empresa:id,nome'), 'message' => 'Usuário criado'], 201);
    }

    public function show(string $id): JsonResponse
    {
        $usuario = User::withoutGlobalScopes()->with('empresa:id,nome')->findOrFail($id);

        return response()->json(['data' => $usuario]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = User::withoutGlobalScopes()->findOrFail($id);

        $dados = $request->validate([
            'nome'       => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,' . $id,
            'password'   => 'sometimes|string|min:8',
            'perfil'     => 'sometimes|in:super_admin,admin,gestor,colaborador',
            'empresa_id' => 'sometimes|nullable|uuid|exists:empresas,id',
            'setor_id'   => 'sometimes|nullable|uuid|exists:setores,id',
            'matricula'  => 'sometimes|nullable|string|max:50',
            'cargo'      => 'sometimes|nullable|string|max:100',
            'status'     => 'sometimes|in:ativo,inativo',
        ]);

        if (isset($dados['password'])) {
            $dados['password'] = Hash::make($dados['password']);
        }

        $usuario->update($dados);

        return response()->json(['data' => $usuario->load('empresa:id,nome'), 'message' => 'Usuário atualizado']);
    }

    public function destroy(string $id): JsonResponse
    {
        $usuario = User::withoutGlobalScopes()->findOrFail($id);
        $usuario->delete();

        return response()->json(['message' => 'Usuário removido']);
    }
}
