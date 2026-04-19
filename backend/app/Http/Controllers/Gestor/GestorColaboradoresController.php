<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GestorColaboradoresController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $gestor = $request->user();

        $colaboradores = User::where('setor_id', $gestor->setor_id)
            ->where('perfil', 'colaborador')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('nome')
            ->get();

        return response()->json(['data' => $colaboradores]);
    }

    public function show(User $colaborador): JsonResponse
    {
        return response()->json(['data' => $colaborador]);
    }

    public function store(Request $request): JsonResponse
    {
        $gestor = $request->user();

        $dados = $request->validate([
            'nome'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'matricula' => 'nullable|string|max:50',
            'cargo'     => 'nullable|string|max:100',
            'password'  => 'required|string|min:8',
            'status'    => 'sometimes|in:ativo,inativo',
        ]);

        $colaborador = User::create([
            'empresa_id' => $gestor->empresa_id,
            'setor_id'   => $gestor->setor_id,
            'gestor_id'  => $gestor->id,
            'nome'       => $dados['nome'],
            'email'      => $dados['email'],
            'matricula'  => $dados['matricula'] ?? null,
            'cargo'      => $dados['cargo'] ?? null,
            'perfil'     => 'colaborador',
            'status'     => $dados['status'] ?? 'ativo',
            'password'   => Hash::make($dados['password']),
        ]);

        return response()->json(['data' => $colaborador, 'message' => 'Colaborador criado'], 201);
    }

    public function update(Request $request, User $colaborador): JsonResponse
    {
        $dados = $request->validate([
            'nome'      => 'sometimes|string|max:255',
            'email'     => 'sometimes|email|unique:users,email,' . $colaborador->id,
            'matricula' => 'sometimes|nullable|string|max:50',
            'cargo'     => 'sometimes|nullable|string|max:100',
            'status'    => 'sometimes|in:ativo,inativo',
            'password'  => 'sometimes|string|min:8',
        ]);

        if (isset($dados['password'])) {
            $dados['password'] = Hash::make($dados['password']);
        }

        $colaborador->update($dados);

        return response()->json(['data' => $colaborador, 'message' => 'Colaborador atualizado']);
    }

    public function destroy(User $colaborador): JsonResponse
    {
        $colaborador->delete();

        return response()->json(['message' => 'Colaborador removido']);
    }
}
