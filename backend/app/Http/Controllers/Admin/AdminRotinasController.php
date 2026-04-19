<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rotina\StoreRotinaRequest;
use App\Http\Requests\Rotina\UpdateRotinaRequest;
use App\Http\Resources\RotinaResource;
use App\Models\Rotina;
use App\Services\RotinaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminRotinasController extends Controller
{
    public function __construct(private RotinaService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Rotina::class);

        return RotinaResource::collection(
            $this->service->listar($request->user(), $request->only(['setor_id', 'status', 'frequencia', 'busca', 'per_page']))
        );
    }

    public function store(StoreRotinaRequest $request): JsonResponse
    {
        $this->authorize('create', Rotina::class);

        $rotina = $this->service->criar($request->user(), $request->validated());
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

        $rotina = $this->service->atualizar($rotina, $request->validated());
        return response()->json(['data' => new RotinaResource($rotina->load(['setor', 'colaboradores']))]);
    }

    public function destroy(Rotina $rotina): JsonResponse
    {
        $this->authorize('delete', $rotina);

        $this->service->desativar($rotina);
        return response()->json(null, 204);
    }

    public function preview(Rotina $rotina): JsonResponse
    {
        $this->authorize('view', $rotina);

        return response()->json([
            'data' => $this->service->previewProximasGeracoes($rotina),
        ]);
    }
}

