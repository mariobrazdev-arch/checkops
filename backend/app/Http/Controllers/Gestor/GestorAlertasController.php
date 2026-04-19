<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestorAlertasController extends Controller
{
    /**
     * US-21 — Lista alertas de falha recorrente do setor (últimos 30 dias).
     */
    public function index(Request $request): JsonResponse
    {
        $gestor = $request->user();

        $alertas = Alerta::with(['rotina:id,titulo', 'colaborador:id,nome,matricula,cargo'])
            ->where('setor_id', $gestor->setor_id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('created_at')
            ->get();

        $dados = $alertas->map(fn ($a) => [
            'id'                  => $a->id,
            'rotina'              => $a->rotina ? ['id' => $a->rotina->id, 'titulo' => $a->rotina->titulo] : null,
            'colaborador'         => $a->colaborador ? [
                'id'        => $a->colaborador->id,
                'nome'      => $a->colaborador->nome,
                'matricula' => $a->colaborador->matricula,
                'cargo'     => $a->colaborador->cargo,
            ] : null,
            'falhas_consecutivas' => $a->falhas_consecutivas,
            'silenciado'          => $a->silenciado(),
            'silenciado_ate'      => $a->silenciado_ate?->toIso8601String(),
            'created_at'          => $a->created_at->toIso8601String(),
        ]);

        return response()->json(['data' => $dados]);
    }

    /**
     * US-21 — Silencia alerta por 7 dias.
     */
    public function marcarCiente(int $id, Request $request): JsonResponse
    {
        $alerta = Alerta::where('id', $id)
            ->where('setor_id', $request->user()->setor_id)
            ->firstOrFail();

        $alerta->update(['silenciado_ate' => now()->addDays(7)]);

        return response()->json(['message' => 'Alerta silenciado por 7 dias']);
    }
}
