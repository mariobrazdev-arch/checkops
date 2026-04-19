<?php

namespace App\Services;

use App\Models\RotinaDiaria;
use App\Models\Setor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * US-17 — Dashboard para gestor: indicadores do próprio setor.
     */
    public function gestor(User $gestor, array $filtros): array
    {
        $cacheKey = "dashboard:gestor:{$gestor->empresa_id}:{$gestor->setor_id}:"
            . ($filtros['periodo'] ?? 'hoje');

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($gestor, $filtros) {
            [$inicio, $fim] = $this->resolverPeriodo($filtros['periodo'] ?? 'hoje');

            $base = RotinaDiaria::query()
                ->whereHas('rotina', fn ($q) => $q->where('setor_id', $gestor->setor_id))
                ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()]);

            return [
                'resumo'                     => $this->resumo($base->clone()),
                'conformidade_colaboradores' => $this->conformidadeColaboradores($base->clone(), $gestor->setor_id, 10),
                'rotinas_criticas'           => $this->rotinasCriticas($base->clone(), 5),
                'justificativas_frequentes'  => $this->justificativasFrequentes($base->clone(), 5),
            ];
        });
    }

    /**
     * US-18 — Dashboard para admin: visão consolidada da empresa.
     */
    public function admin(User $admin, array $filtros): array
    {
        $cacheKey = "dashboard:admin:{$admin->empresa_id}:"
            . ($filtros['periodo'] ?? 'hoje')
            . ':' . ($filtros['setor_id'] ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($admin, $filtros) {
            [$inicio, $fim] = $this->resolverPeriodo($filtros['periodo'] ?? 'hoje');

            $base = RotinaDiaria::query()
                ->whereHas('rotina', fn ($q) => $q->where('empresa_id', $admin->empresa_id));

            if (!empty($filtros['setor_id'])) {
                $base->whereHas('rotina', fn ($q) => $q->where('setor_id', $filtros['setor_id']));
            }

            $base->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()]);

            // Histórico diário — últimos 30 dias (sempre — ignora filtro periodo)
            $historicoDiario = $this->historicoDiario($admin->empresa_id, $filtros['setor_id'] ?? null);

            return [
                'resumo'                     => $this->resumo($base->clone()),
                'conformidade_colaboradores' => $this->conformidadeColaboradores($base->clone(), null, 10),
                'rotinas_criticas'           => $this->rotinasCriticas($base->clone(), 5),
                'justificativas_frequentes'  => $this->justificativasFrequentes($base->clone(), 5),
                'conformidade_por_setor'     => $this->conformidadePorSetor($admin->empresa_id, $inicio, $fim),
                'historico_diario'           => $historicoDiario,
                'ranking_setores'            => $this->rankingSetores($admin->empresa_id, $inicio, $fim),
                'ranking_colaboradores'      => $this->rankingColaboradores($admin->empresa_id, $inicio, $fim, 10),
            ];
        });
    }

    // ─── Helpers privados ─────────────────────────────────────────────────────

    private function resolverPeriodo(string $periodo): array
    {
        return match ($periodo) {
            'semana' => [now()->startOfWeek(),  now()->endOfWeek()],
            'mes'    => [now()->startOfMonth(), now()->endOfMonth()],
            default  => [today(), today()],
        };
    }

    private function resumo($query): array
    {
        $resultados = $query->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $total         = array_sum($resultados);
        $realizadas    = (int) ($resultados['realizada']     ?? 0);
        $pendentes     = (int) ($resultados['pendente']      ?? 0);
        $naoRealizadas = (int) ($resultados['nao_realizada'] ?? 0);
        $atrasadas     = (int) ($resultados['atrasada']      ?? 0);

        $percentual = $total > 0 ? round(($realizadas / $total) * 100, 1) : 0.0;

        return [
            'total'          => $total,
            'concluidas'     => $realizadas,
            'pendentes'      => $pendentes,
            'nao_realizadas' => $naoRealizadas,
            'atrasadas'      => $atrasadas,
            'percentual'     => $percentual,
        ];
    }

    private function conformidadeColaboradores($query, ?string $setorId, int $limit): array
    {
        if ($setorId) {
            $query->whereHas('colaborador', fn ($q) => $q->where('setor_id', $setorId));
        }

        $rows = $query->select(
            'colaborador_id',
            DB::raw('count(*) as total'),
            DB::raw("sum(case when status = 'realizada' then 1 else 0 end) as realizadas")
        )
            ->groupBy('colaborador_id')
            ->with('colaborador:id,nome,matricula,cargo')
            ->get();

        return $rows->map(fn ($r) => [
            'colaborador' => $r->colaborador ? [
                'id'        => $r->colaborador->id,
                'nome'      => $r->colaborador->nome,
                'matricula' => $r->colaborador->matricula,
                'cargo'     => $r->colaborador->cargo,
            ] : null,
            'total'      => (int) $r->total,
            'realizadas' => (int) $r->realizadas,
            'percentual' => $r->total > 0 ? round(($r->realizadas / $r->total) * 100, 1) : 0.0,
        ])
            ->sortBy('percentual')
            ->take($limit)
            ->values()
            ->toArray();
    }

    private function rotinasCriticas($query, int $limit): array
    {
        return $query->whereIn('status', ['nao_realizada', 'atrasada'])
            ->select('rotina_id', DB::raw('count(*) as falhas'))
            ->groupBy('rotina_id')
            ->orderByDesc('falhas')
            ->limit($limit)
            ->with('rotina:id,titulo')
            ->get()
            ->map(fn ($r) => [
                'rotina' => $r->rotina ? ['id' => $r->rotina->id, 'titulo' => $r->rotina->titulo] : null,
                'falhas' => (int) $r->falhas,
            ])
            ->toArray();
    }

    private function justificativasFrequentes($query, int $limit): array
    {
        return $query->whereNotNull('justificativa')
            ->select('justificativa', DB::raw('count(*) as count'))
            ->groupBy('justificativa')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('count', 'justificativa')
            ->map(fn ($count, $texto) => ['texto' => $texto, 'count' => (int) $count])
            ->values()
            ->toArray();
    }

    private function conformidadePorSetor(string $empresaId, Carbon $inicio, Carbon $fim): array
    {
        $setores = Setor::with('gestor:id,nome')->where('empresa_id', $empresaId)->get(['id', 'nome', 'gestor_id']);

        return $setores->map(function ($setor) use ($inicio, $fim) {
            $rows = RotinaDiaria::whereHas('rotina', fn ($q) => $q->where('setor_id', $setor->id))
                ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $total      = array_sum($rows);
            $concluidas = (int) ($rows['realizada'] ?? 0);

            return [
                'setor_id'    => $setor->id,
                'setor_nome'  => $setor->nome,
                'gestor_nome' => $setor->gestor?->nome,
                'total'       => $total,
                'concluidas'  => $concluidas,
                'percentual'  => $total > 0 ? round(($concluidas / $total) * 100, 1) : 0.0,
            ];
        })->toArray();
    }

    private function historicoDiario(string $empresaId, ?string $setorId): array
    {
        $inicio = now()->subDays(29)->startOfDay();
        $fim    = now()->endOfDay();

        $query = RotinaDiaria::whereHas('rotina', function ($q) use ($empresaId, $setorId) {
            $q->where('empresa_id', $empresaId);
            if ($setorId) $q->where('setor_id', $setorId);
        })
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->select(
                'data',
                DB::raw('count(*) as total'),
                DB::raw("sum(case when status = 'realizada' then 1 else 0 end) as realizadas")
            )
            ->groupBy('data')
            ->orderBy('data')
            ->get();

        return $query->map(fn ($r) => [
            'data'       => $r->data->toDateString(),
            'percentual' => $r->total > 0 ? round(($r->realizadas / $r->total) * 100, 1) : 0.0,
        ])->toArray();
    }

    private function rankingSetores(string $empresaId, Carbon $inicio, Carbon $fim): array
    {
        // Reutiliza conformidadePorSetor e ordena ASC (piores primeiro)
        $dados = $this->conformidadePorSetor($empresaId, $inicio, $fim);
        usort($dados, fn ($a, $b) => $a['percentual'] <=> $b['percentual']);
        return $dados;
    }

    private function rankingColaboradores(string $empresaId, Carbon $inicio, Carbon $fim, int $limit): array
    {
        return RotinaDiaria::whereHas('rotina', fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->whereIn('status', ['nao_realizada', 'atrasada'])
            ->select('colaborador_id', DB::raw('count(*) as pendencias'))
            ->groupBy('colaborador_id')
            ->orderByDesc('pendencias')
            ->limit($limit)
            ->with('colaborador:id,nome,matricula,cargo')
            ->get()
            ->map(fn ($r) => [
                'colaborador' => $r->colaborador ? [
                    'id'        => $r->colaborador->id,
                    'nome'      => $r->colaborador->nome,
                    'matricula' => $r->colaborador->matricula,
                ] : null,
                'pendencias' => (int) $r->pendencias,
            ])
            ->toArray();
    }
}
