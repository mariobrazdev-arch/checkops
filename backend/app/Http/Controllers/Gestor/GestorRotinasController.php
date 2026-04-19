<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rotina\StoreRotinaRequest;
use App\Http\Requests\Rotina\UpdateRotinaRequest;
use App\Http\Requests\RotinaDiaria\ReabrirRotinaRequest;
use App\Http\Resources\RotinaResource;
use App\Http\Resources\RotinaDiariaResource;
use App\Models\Rotina;
use App\Models\RotinaDiaria;
use App\Services\RotinaService;
use App\Services\RotinaDiariaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GestorRotinasController extends Controller
{
    public function __construct(
        private RotinaService $rotinaService,
        private RotinaDiariaService $rotinaDiariaService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Rotina::class);

        return RotinaResource::collection(
            $this->rotinaService->listar($request->user(), $request->only(['status', 'frequencia', 'busca', 'per_page']))
        );
    }

    public function store(StoreRotinaRequest $request): JsonResponse
    {
        $this->authorize('create', Rotina::class);

        $rotina = $this->rotinaService->criar($request->user(), $request->validated());
        return response()->json(['data' => new RotinaResource($rotina->load(['setor', 'colaboradores']))], 201);
    }

    public function show(Rotina $rotina): JsonResponse
    {
        $this->authorize('view', $rotina);

        return response()->json(['data' => new RotinaResource($rotina->load(['setor', 'colaboradores']))]);
    }

    public function update(UpdateRotinaRequest $request, Rotina $rotina): JsonResponse
    {
        $this->authorize('update', $rotina);

        $rotina = $this->rotinaService->atualizar($rotina, $request->validated());
        return response()->json(['data' => new RotinaResource($rotina->load(['setor', 'colaboradores']))]);
    }

    public function destroy(Rotina $rotina): JsonResponse
    {
        $this->authorize('delete', $rotina);

        $this->rotinaService->desativar($rotina);
        return response()->json(null, 204);
    }

    public function preview(Rotina $rotina): JsonResponse
    {
        $this->authorize('view', $rotina);

        return response()->json([
            'data' => $this->rotinaService->previewProximasGeracoes($rotina),
        ]);
    }

    public function reabrir(ReabrirRotinaRequest $request, RotinaDiaria $rotinaDiaria): JsonResponse
    {
        $this->authorize('reabrir', $rotinaDiaria);

        try {
            $rd = $this->rotinaDiariaService->reabrir(
                $rotinaDiaria,
                $request->user(),
                $request->validated()['justificativa']
            );
            return response()->json(['data' => new RotinaDiariaResource($rd)]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}


