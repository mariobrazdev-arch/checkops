<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditoriaResource;
use App\Jobs\GerarAuditoriaCsv;
use App\Models\Auditoria;
use App\Traits\ResolvesEmpresaId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditoriaController extends Controller
{
    use ResolvesEmpresaId;

    /**
     * US-16 — Lista auditoria com filtros e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        $empresaId = $this->resolveEmpresaId($request);

        $query = Auditoria::with('usuario')
            ->where('empresa_id', $empresaId)
            ->orderByDesc('created_at');

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }
        if ($request->filled('acao')) {
            $query->where('acao', $request->acao);
        }
        if ($request->filled('entidade')) {
            $query->where('entidade', $request->entidade);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }

        $paginated = $query->paginate(50);

        return response()->json([
            'data' => AuditoriaResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * US-16 — Exporta CSV de auditoria em background.
     */
    public function exportar(Request $request): JsonResponse
    {
        $filtros = $request->only(['usuario_id', 'acao', 'entidade', 'data_inicio', 'data_fim']);
        $filtros['empresa_id'] = $this->resolveEmpresaId($request);

        $jobId = (string) \Illuminate\Support\Str::uuid();

        GerarAuditoriaCsv::dispatch($filtros, $request->user()->id, $jobId)
            ->onQueue('reports');

        return response()->json(['job_id' => $jobId], 202);
    }
}
