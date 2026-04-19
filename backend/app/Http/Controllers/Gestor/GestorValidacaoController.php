<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use App\Http\Requests\RotinaDiaria\ReabrirRotinaRequest;
use App\Http\Resources\RotinaDiariaDetalheResource;
use App\Models\RotinaDiaria;
use App\Services\RotinaDiariaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestorValidacaoController extends Controller
{
    public function __construct(private RotinaDiariaService $service) {}
    /**
     * Lista rotinas realizadas do setor do gestor.
     * Filtros: colaborador_id, data_inicio, data_fim
     * (US-14)
     */
    public function index(Request $request): JsonResponse
    {
        $gestor = $request->user();

        $query = RotinaDiaria::with(['rotina', 'colaborador'])
            ->whereHas('rotina', fn ($q) => $q->where('setor_id', $gestor->setor_id));

        // status: 'realizada' (padrão para validação de fotos) ou qualquer valor, ou null para todos
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('colaborador_id')) {
            $query->where('colaborador_id', $request->colaborador_id);
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('data', '>=', $request->data_inicio);
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data', '<=', $request->data_fim);
        }

        $paginado = $query->orderBy('data', 'desc')->orderBy('colaborador_id')->paginate($request->per_page ?? 20);

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

    /**
     * Detalhe completo de uma rotina diária — com foto assinada + metadados.
     * (US-14)
     */
    public function show(RotinaDiaria $rotinaDiaria): JsonResponse
    {
        $this->authorize('view', $rotinaDiaria);

        $rotinaDiaria->load(['rotina', 'colaborador']);

        return response()->json([
            'data' => new RotinaDiariaDetalheResource($rotinaDiaria),
        ]);
    }

    public function reabrir(ReabrirRotinaRequest $request, RotinaDiaria $rotinaDiaria): JsonResponse
    {
        $this->authorize('reabrir', $rotinaDiaria);

        try {
            $rd = $this->service->reabrir(
                $rotinaDiaria,
                $request->user(),
                $request->validated()['justificativa']
            );
            return response()->json(['data' => new RotinaDiariaDetalheResource($rd->load(['rotina', 'colaborador']))]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
