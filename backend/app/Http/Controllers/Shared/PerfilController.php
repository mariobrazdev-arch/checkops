<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PerfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user()->load('setor'))]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        // Troca de senha
        if ($request->has('senha_atual') || $request->has('nova_senha')) {
            $request->validate([
                'senha_atual'             => ['required', 'string'],
                'nova_senha'              => ['required', 'string', 'min:8', 'confirmed'],
                'nova_senha_confirmation' => ['required', 'string'],
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
            'nome'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', "unique:users,email,{$user->id}"],
            'telefone'        => ['nullable', 'string', 'max:20'],
            'cpf'             => ['nullable', 'string', 'max:14'],
            'sexo'            => ['nullable', 'string', 'in:masculino,feminino,outro,nao_informado'],
            'data_nascimento' => ['nullable', 'date'],
        ]);

        $user->update($dados);

        return response()->json(['data' => new UserResource($user->fresh()->load('setor'))]);
    }

    public function uploadFoto(Request $request): JsonResponse
    {
        $request->validate(['foto_base64' => ['required', 'string']]);

        $base64 = $request->input('foto_base64');

        // Remove data URI prefix se presente (data:image/jpeg;base64,...)
        if (str_contains($base64, ',')) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }

        $decoded = base64_decode($base64, strict: true);
        if ($decoded === false) {
            return response()->json(['message' => 'Imagem inválida.'], 422);
        }

        // Valida que é realmente uma imagem verificando magic bytes
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($decoded);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
            return response()->json(['message' => 'O arquivo deve ser uma imagem (JPEG, PNG ou WebP).'], 422);
        }

        $ext  = match($mime) { 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', default => 'jpg' };
        $user = $request->user();

        if ($user->foto_perfil_path) {
            Storage::disk('public')->delete($user->foto_perfil_path);
        }

        $filename = uniqid() . '.' . $ext;
        $path     = "usuarios/fotoperfil/{$user->id}/{$filename}";
        Storage::disk('public')->put($path, $decoded);

        $user->update(['foto_perfil_path' => $path]);

        return response()->json([
            'data'    => new UserResource($user->fresh()->load('setor')),
            'message' => 'Foto atualizada',
        ]);
    }

    public function removerFoto(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->foto_perfil_path) {
            Storage::disk('public')->delete($user->foto_perfil_path);
            $user->update(['foto_perfil_path' => null]);
        }

        return response()->json(['message' => 'Foto removida']);
    }
}
