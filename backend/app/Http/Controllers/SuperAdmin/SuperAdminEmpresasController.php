<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperAdminEmpresasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $empresas = Empresa::with('plano:id,nome')
            ->withCount('users')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('nome')
            ->get();

        return response()->json(['data' => $empresas]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome'             => 'required|string|max:255',
            'cnpj'             => 'required|string|max:18|unique:empresas,cnpj',
            'telefone'         => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:255',
            'responsavel'      => 'nullable|string|max:255',
            'plano_id'         => 'nullable|uuid|exists:planos,id',
            'admin_nome'       => 'required|string|max:255',
            'admin_email'      => 'required|email|max:255',
            'admin_password'   => 'required|string|min:8',
        ]);

        $empresa = Empresa::create([
            'nome'        => $dados['nome'],
            'cnpj'        => $dados['cnpj'],
            'telefone'    => $dados['telefone'] ?? null,
            'email'       => $dados['email'] ?? null,
            'responsavel' => $dados['responsavel'] ?? null,
            'plano_id'    => $dados['plano_id'] ?? null,
            'status'      => 'ativo',
        ]);

        $admin = User::create([
            'empresa_id' => $empresa->id,
            'nome'       => $dados['admin_nome'],
            'email'      => $dados['admin_email'],
            'password'   => Hash::make($dados['admin_password']),
            'perfil'     => 'admin',
            'status'     => 'ativo',
        ]);

        return response()->json([
            'data'    => $empresa->load('plano:id,nome'),
            'admin'   => $admin->only('id', 'nome', 'email'),
            'message' => 'Empresa e admin criados com sucesso',
        ], 201);
    }

    public function show(Empresa $empresa): JsonResponse
    {
        return response()->json(['data' => $empresa->load('plano:id,nome')->loadCount('users')]);
    }

    public function update(Request $request, Empresa $empresa): JsonResponse
    {
        $dados = $request->validate([
            'nome'        => 'sometimes|string|max:255',
            'cnpj'        => 'sometimes|string|max:18|unique:empresas,cnpj,' . $empresa->id,
            'telefone'    => 'sometimes|nullable|string|max:20',
            'email'       => 'sometimes|nullable|email|max:255',
            'responsavel' => 'sometimes|nullable|string|max:255',
            'plano_id'    => 'sometimes|nullable|uuid|exists:planos,id',
            'status'      => 'sometimes|in:ativo,inativo',
        ]);

        $empresa->update($dados);

        return response()->json(['data' => $empresa->load('plano:id,nome'), 'message' => 'Empresa atualizada']);
    }

    public function destroy(Empresa $empresa): JsonResponse
    {
        $empresa->delete();

        return response()->json(['message' => 'Empresa removida']);
    }
}
