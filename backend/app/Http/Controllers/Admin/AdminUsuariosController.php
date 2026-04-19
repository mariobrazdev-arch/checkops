<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUsuariosController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $usuarios = User::with('setor:id,nome')
            ->when($request->perfil, fn($q) => $q->where('perfil', $request->perfil))
            ->when($request->setor_id, fn($q) => $q->where('setor_id', $request->setor_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('nome')
            ->get();

        return response()->json(['data' => $usuarios]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'matricula' => 'nullable|string|max:50',
            'cargo'     => 'nullable|string|max:100',
            'perfil'    => 'required|in:admin,gestor,colaborador',
            'setor_id'  => 'nullable|uuid|exists:setores,id',
            'gestor_id' => 'nullable|uuid|exists:users,id',
            'status'    => 'sometimes|in:ativo,inativo',
            'password'  => 'required|string|min:8',
        ]);

        $dados['empresa_id'] = $request->user()->empresa_id;
        $dados['status'] = $dados['status'] ?? 'ativo';
        $dados['password'] = Hash::make($dados['password']);

        $usuario = User::create($dados);
        $usuario->load('setor:id,nome');

        return response()->json(['data' => $usuario, 'message' => 'Usuário criado'], 201);
    }

    public function show(User $usuario): JsonResponse
    {
        return response()->json(['data' => $usuario->load('setor:id,nome')]);
    }

    public function update(Request $request, User $usuario): JsonResponse
    {
        $dados = $request->validate([
            'nome'      => 'sometimes|string|max:255',
            'email'     => 'sometimes|email|unique:users,email,' . $usuario->id,
            'matricula' => 'sometimes|nullable|string|max:50',
            'cargo'     => 'sometimes|nullable|string|max:100',
            'perfil'    => 'sometimes|in:admin,gestor,colaborador',
            'setor_id'  => 'sometimes|nullable|uuid|exists:setores,id',
            'gestor_id' => 'sometimes|nullable|uuid|exists:users,id',
            'status'    => 'sometimes|in:ativo,inativo',
            'password'  => 'sometimes|string|min:8',
        ]);

        if (isset($dados['password'])) {
            $dados['password'] = Hash::make($dados['password']);
        }

        $usuario->update($dados);

        return response()->json(['data' => $usuario->load('setor:id,nome'), 'message' => 'Usuário atualizado']);
    }

    public function destroy(User $usuario): JsonResponse
    {
        $usuario->delete();

        return response()->json(['message' => 'Usuário removido']);
    }
}
