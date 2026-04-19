<?php

namespace App\Http\Controllers\Colaborador;

use App\Http\Controllers\Controller;
use App\Http\Resources\RotinaDiariaDetalheResource;
use App\Services\RotinaDiariaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColaboradorHistoricoController extends Controller
{
    public function __construct(private RotinaDiariaService $service) {}

    /**
     * Histórico de rotinas do colaborador autenticado.
     * Filtros: data_inicio, data_fim, status
     * Ordenação: data DESC | Paginação: 20 por página
     * (US-15)
     */
    public function index(Request $request): JsonResponse
    {
        $paginado = $this->service->historico(
            $request->user(),
            $request->only(['data_inicio', 'data_fim', 'status', 'per_page'])
        );

        return response()->json([
            'data' => RotinaDiariaDetalheResource::collection($paginado),
            'meta' => [
                'current_page' => $paginado->currentPage(),
                'last_page'    => $paginado->lastPage(),
                'per_page'     => $paginado->perPage(),
                'total'        => $paginado->total(),
            ],
        ]);
    }
}
