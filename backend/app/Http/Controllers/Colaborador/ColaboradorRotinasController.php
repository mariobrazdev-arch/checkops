<?php

namespace App\Http\Controllers\Colaborador;

use App\Http\Controllers\Controller;
use App\Http\Requests\RotinaDiaria\ResponderNaoRequest;
use App\Http\Requests\RotinaDiaria\ResponderSimRequest;
use App\Http\Resources\RotinaDiariaResource;
use App\Models\RotinaDiaria;
use App\Services\RotinaDiariaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ColaboradorRotinasController extends Controller
{
    public function __construct(private RotinaDiariaService $service) {}

    public function hoje(Request $request): AnonymousResourceCollection
    {
        $rotinas = $this->service->listarDoDia($request->user(), today());
        return RotinaDiariaResource::collection($rotinas);
    }

    public function responderSim(ResponderSimRequest $request, RotinaDiaria $rotinaDiaria): JsonResponse
    {
        $this->authorize('responder', $rotinaDiaria);

        try {
            $rd = $this->service->responderSim($rotinaDiaria, $request->validated());
            return response()->json(['data' => new RotinaDiariaResource($rd)]);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function responderNao(ResponderNaoRequest $request, RotinaDiaria $rotinaDiaria): JsonResponse
    {
        $this->authorize('responder', $rotinaDiaria);

        try {
            $rd = $this->service->responderNao($rotinaDiaria, $request->validated()['justificativa']);
            return response()->json(['data' => new RotinaDiariaResource($rd)]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function historico(Request $request): AnonymousResourceCollection
    {
        $paginator = $this->service->historico(
            $request->user(),
            $request->only(['data_inicio', 'data_fim', 'status', 'per_page'])
        );
        return RotinaDiariaResource::collection($paginator);
    }
}

