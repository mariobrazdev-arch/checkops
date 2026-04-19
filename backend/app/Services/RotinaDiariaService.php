<?php

namespace App\Services;

use App\Models\Rotina;
use App\Models\RotinaDiaria;
use App\Models\User;
use Carbon\Carbon;

class RotinaDiariaService
{
    public function __construct(
        private FotoService $fotoService,
        private RotinaService $rotinaService,
    ) {}

    // ─── Listagem ───────────────────────────────────────────────────────────

    public function listarDoDia(User $colaborador, Carbon $data): \Illuminate\Database\Eloquent\Collection
    {
        return RotinaDiaria::with('rotina')
            ->where('colaborador_id', $colaborador->id)
            ->whereDate('data', $data)
            ->orderByRaw("CASE status
                WHEN 'atrasada'       THEN 1
                WHEN 'pendente'       THEN 2
                WHEN 'realizada'      THEN 3
                WHEN 'nao_realizada'  THEN 4
                ELSE 5
            END")
            ->get();
    }

    public function historico(User $colaborador, array $filtros): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = RotinaDiaria::with(['rotina', 'colaborador'])
            ->where('colaborador_id', $colaborador->id);

        if (!empty($filtros['data_inicio'])) {
            $query->whereDate('data', '>=', $filtros['data_inicio']);
        }

        if (!empty($filtros['data_fim'])) {
            $query->whereDate('data', '<=', $filtros['data_fim']);
        }

        if (!empty($filtros['status'])) {
            $statuses = is_array($filtros['status'])
                ? $filtros['status']
                : explode(',', $filtros['status']);
            $query->whereIn('status', $statuses);
        }

        return $query->orderBy('data', 'desc')->paginate($filtros['per_page'] ?? 20);
    }

    // ─── Geração para uma rotina específica ────────────────────────────────────

    public function gerarParaRotina(Rotina $rotina, Carbon $data): int
    {
        if (!$this->rotinaService->rotinaRodaNaData($rotina, $data)) {
            return 0;
        }

        $rotina->loadMissing('colaboradores');

        if ($rotina->colaboradores->isNotEmpty()) {
            $colaboradores = $rotina->colaboradores->where('status', 'ativo');
        } else {
            $colaboradores = User::withoutGlobalScopes()
                ->where('setor_id', $rotina->setor_id)
                ->where('perfil', 'colaborador')
                ->where('status', 'ativo')
                ->get();
        }

        $total = 0;
        foreach ($colaboradores as $colaborador) {
            $rd = RotinaDiaria::firstOrCreate(
                ['rotina_id' => $rotina->id, 'colaborador_id' => $colaborador->id, 'data' => $data->toDateString()],
                ['status' => 'pendente']
            );
            if ($rd->wasRecentlyCreated) $total++;
        }

        return $total;
    }

    // ─── Respostas ──────────────────────────────────────────────────────────

    public function responderSim(RotinaDiaria $rd, array $dados): RotinaDiaria
    {
        if (!in_array($rd->status, ['pendente', 'atrasada'])) {
            throw new \DomainException('Rotina já foi respondida ou está fechada.');
        }

        // RN-03: se foto obrigatória, valida metadados de câmera
        $fotoPath = null;
        if ($rd->rotina->foto_obrigatoria) {
            if (empty($dados['foto_base64'])) {
                throw new \DomainException('Foto é obrigatória para esta rotina.');
            }

            // Valida metadados + registra alertas de auditoria se necessário
            $this->fotoService->validarMetadados($dados, $rd);

            // Salva PATH (nunca URL) — URL assinada gerada sob demanda pelo Resource (RN-10)
            $fotoPath = $this->fotoService->processar($dados['foto_base64'], $dados, $rd);
        }

        $rd->update([
            'status'             => 'realizada',
            'data_hora_resposta' => now(), // sempre horário do servidor — RN-03
            'foto_url'           => $fotoPath,
            'foto_lat'           => $dados['foto_lat'] ?? null,
            'foto_lng'           => $dados['foto_lng'] ?? null,
            'foto_timestamp'     => isset($dados['foto_timestamp'])
                ? Carbon::createFromTimestamp((int) $dados['foto_timestamp'])
                : null,
            'foto_device_id'     => $dados['foto_device_id'] ?? null,
        ]);

        return $rd->fresh('rotina');
    }

    public function responderNao(RotinaDiaria $rd, string $justificativa): RotinaDiaria
    {
        if (!in_array($rd->status, ['pendente', 'atrasada'])) {
            throw new \DomainException('Rotina já foi respondida ou está fechada.');
        }

        if ($rd->rotina->justif_obrigatoria && strlen(trim($justificativa)) < 20) {
            throw new \DomainException('Justificativa deve ter no mínimo 20 caracteres.');
        }

        $rd->update([
            'status'             => 'nao_realizada',
            'data_hora_resposta' => now(),
            'justificativa'      => $justificativa,
        ]);

        return $rd->fresh('rotina');
    }

    public function reabrir(RotinaDiaria $rd, User $gestor, string $justificativa): RotinaDiaria
    {
        // RN-05: apenas gestor do setor ou admin pode reabrir
        if (!in_array($rd->status, ['realizada', 'nao_realizada'])) {
            throw new \DomainException('Apenas rotinas já respondidas podem ser reabertas.');
        }

        $rd->update([
            'status'                  => 'pendente',
            'data_hora_resposta'      => null,
            'reaberta_por'            => $gestor->id,
            'reaberta_justificativa'  => $justificativa,
        ]);

        return $rd->fresh('rotina');
    }

    // ─── Geração diária (chamada pelo Command) ──────────────────────────────

    public function gerarDoDia(Carbon $data): int
    {
        $total = 0;

        $rotinas = Rotina::with(['setor', 'colaboradores'])
            ->withoutGlobalScopes() // command roda sem auth
            ->where('status', 'ativa')
            ->where('data_inicio', '<=', $data->toDateString())
            ->where(function ($q) use ($data) {
                $q->whereNull('data_fim')
                  ->orWhere('data_fim', '>=', $data->toDateString());
            })
            ->get();

        foreach ($rotinas as $rotina) {
            if (!$this->rotinaService->rotinaRodaNaData($rotina, $data)) {
                continue;
            }

            // Se há colaboradores específicos associados, usa eles; senão, todos do setor
            if ($rotina->colaboradores->isNotEmpty()) {
                $colaboradores = $rotina->colaboradores->where('status', 'ativo');
            } else {
                $colaboradores = User::withoutGlobalScopes()
                    ->where('setor_id', $rotina->setor_id)
                    ->where('perfil', 'colaborador')
                    ->where('status', 'ativo')
                    ->get();
            }

            foreach ($colaboradores as $colaborador) {
                $rd = RotinaDiaria::firstOrCreate(
                    [
                        'rotina_id'      => $rotina->id,
                        'colaborador_id' => $colaborador->id,
                        'data'           => $data->toDateString(),
                    ],
                    ['status' => 'pendente']
                );

                if ($rd->wasRecentlyCreated) {
                    $total++;
                }
            }
        }

        return $total;
    }

    // ─── Scheduler auxiliares ───────────────────────────────────────────────

    public function marcarAtrasadas(Carbon $data): int
    {
        return RotinaDiaria::where('status', 'pendente')
            ->whereDate('data', $data)
            ->whereHas('rotina', function ($q) {
                // horario_previsto + 30min < agora
                $q->whereRaw(
                    "horario_previsto::time < (NOW() - INTERVAL '30 minutes')::time"
                );
            })
            ->update(['status' => 'atrasada']);
    }

    public function fecharDia(Carbon $data): int
    {
        $pendentes = RotinaDiaria::whereIn('status', ['pendente', 'atrasada'])
            ->whereDate('data', $data)
            ->get(['id', 'rotina_id', 'colaborador_id']);

        if ($pendentes->isEmpty()) return 0;

        RotinaDiaria::whereIn('id', $pendentes->pluck('id'))
            ->update([
                'status'             => 'nao_realizada',
                'data_hora_resposta' => now(),
                'justificativa'      => 'Não respondida até o fechamento do dia.',
            ]);

        // US-21: verifica falhas recorrentes para cada rotina fechada
        foreach ($pendentes as $rd) {
            \App\Jobs\VerificarFalhasRecorrentes::dispatch($rd->rotina_id, $rd->colaborador_id)
                ->onQueue('default');
        }

        return $pendentes->count();
    }
}
