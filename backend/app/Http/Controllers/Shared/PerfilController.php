<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PerfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        // Troca de senha
        if ($request->has('senha_atual') || $request->has('nova_senha')) {
            $request->validate([
                'senha_atual'              => ['required', 'string'],
                'nova_senha'               => ['required', 'string', 'min:8', 'confirmed'],
                'nova_senha_confirmation'  => ['required', 'string'],
            ]);

            if (!Hash::check($request->senha_atual, $user->password)) {
                throw ValidationException::withMessages([
                    'senha_atual' => ['Senha atual incorreta.'],
                ]);
            }

            $user->update(['password' => $request->nova_senha]);

            return response()->json(['message' => 'Senha alterada com sucesso.']);
        }

        // Dados pessoais
        $dados = $request->validate([
            'nome'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', "unique:users,email,{$user->id}"],
            'telefone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($dados);

        return response()->json(['data' => new UserResource($user->fresh()->load('setor'))]);
    }
}
