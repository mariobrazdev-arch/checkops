<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RotinaDiariaDetalheResource;
use App\Jobs\GerarRelatorioCsv;
use App\Models\RotinaDiaria;
use App\Traits\ResolvesEmpresaId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AdminRelatoriosController extends Controller
{
    use ResolvesEmpresaId;

    /**
     * US-19 — Listagem paginada de rotinas_diarias com filtros completos.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->baseQuery($request);
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
     * KPIs totalizados para os filtros ativos.
     */
    public function resumo(Request $request): JsonResponse
    {
        $rows = $this->baseQuery($request)
            ->reorder()
            ->selectRaw("status, count(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $total         = $rows->sum();
        $realizadas    = (int) ($rows['realizada']     ?? 0);
        $naoRealizadas = (int) ($rows['nao_realizada'] ?? 0);
        $atrasadas     = (int) ($rows['atrasada']      ?? 0);
        $pendentes     = (int) ($rows['pendente']      ?? 0);
        $conformidade  = $total > 0 ? round($realizadas / $total * 100, 1) : 0.0;

        return response()->json(['data' => compact(
            'total', 'realizadas', 'naoRealizadas', 'atrasadas', 'pendentes', 'conformidade'
        )]);
    }

    private function baseQuery(Request $request)
    {
        $empresaId = $this->resolveEmpresaId($request);

        $query = RotinaDiaria::with(['rotina.setor', 'colaborador', 'fotos'])
            ->whereHas('rotina', fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderByDesc('data')
            ->orderByDesc('created_at');

        if ($request->filled('setor_id')) {
            $query->whereHas('rotina', fn ($q) => $q->where('setor_id', $request->setor_id));
        }
        if ($request->filled('colaborador')) {
            $query->whereHas('colaborador', fn ($q) => $q->where('nome', 'ilike', '%'.$request->colaborador.'%'));
        }
        if ($request->filled('rotina')) {
            $query->whereHas('rotina', fn ($q) => $q->where('titulo', 'ilike', '%'.$request->rotina.'%'));
        }
        if ($request->filled('status')) {
            $statuses = explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('data', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('data', '<=', $request->data_fim);
        }

        return $query;
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
        $filtros['empresa_id'] = $this->resolveEmpresaId($request);

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
