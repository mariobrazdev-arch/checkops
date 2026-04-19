<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RotinaDiariaDetalheResource;
use App\Jobs\GerarRelatorioCsv;
use App\Models\RotinaDiaria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AdminRelatoriosController extends Controller
{
    /**
     * US-19 — Listagem paginada de rotinas_diarias com filtros completos.
     */
    public function index(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $query = RotinaDiaria::with(['rotina.setor', 'colaborador'])
            ->whereHas('rotina', fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderByDesc('data')
            ->orderByDesc('created_at');

        if ($request->filled('setor_id')) {
            $query->whereHas('rotina', fn ($q) => $q->where('setor_id', $request->setor_id));
        }
        if ($request->filled('colaborador_id')) {
            $query->where('colaborador_id', $request->colaborador_id);
        }
        if ($request->filled('rotina_id')) {
            $query->where('rotina_id', $request->rotina_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('data', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('data', '<=', $request->data_fim);
        }

        $paginated = $query->paginate(50);

        return response()->json([
            'data' => RotinaDiariaDetalheResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * US-19 — Dispara geração de CSV em background.
     */
    public function exportar(Request $request): JsonResponse
    {
        $filtros = $request->only([
            'setor_id', 'colaborador_id', 'rotina_id',
            'status', 'data_inicio', 'data_fim',
        ]);
        $filtros['empresa_id'] = $request->user()->empresa_id;

        $jobId = (string) Str::uuid();

        GerarRelatorioCsv::dispatch($filtros, $request->user()->id, $jobId)
            ->onQueue('reports');

        return response()->json(['job_id' => $jobId], 202);
    }

    /**
     * US-19 — Polling: verifica se o Job terminou e retorna URL.
     */
    public function status(string $jobId): JsonResponse
    {
        $status = Cache::get("job:{$jobId}:status", 'pending');
        $url    = Cache::get("job:{$jobId}:url");

        return response()->json(compact('status', 'url'));
    }
}

