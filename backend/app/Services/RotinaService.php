<?php

namespace App\Services;

use App\Models\Rotina;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RotinaService
{
    public function listar(User $user, array $filtros): LengthAwarePaginator
    {
        $query = Rotina::with(['setor', 'colaboradores']);

        // Escopa por empresa (via filtro explícito ou pelo usuário)
        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        } elseif ($user->empresa_id) {
            $query->where('empresa_id', $user->empresa_id);
        }

        if ($user->perfil === 'gestor') {
            $query->where('setor_id', $user->setor_id);
        }

        if (!empty($filtros['setor_id'])) {
            $query->where('setor_id', $filtros['setor_id']);
        }

        if (!empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        if (!empty($filtros['frequencia'])) {
            $query->where('frequencia', $filtros['frequencia']);
        }

        if (!empty($filtros['busca'])) {
            $query->where('titulo', 'ilike', '%' . $filtros['busca'] . '%');
        }

        return $query->orderBy('titulo')->paginate($filtros['per_page'] ?? 20);
    }

    public function criar(User $user, array $dados): Rotina
    {
        if ($user->perfil === 'gestor') {
            $dados['setor_id'] = $user->setor_id;
        }

        // empresa_id pode vir pré-injetado pelo controller (caso super_admin)
        $dados['empresa_id'] = $dados['empresa_id'] ?? $user->empresa_id;
        $dados['status']     = 'ativa';

        $colaboradorIds = $dados['colaborador_ids'] ?? [];
        unset($dados['colaborador_ids']);

        $rotina = Rotina::create($dados);

        if (!empty($colaboradorIds)) {
            $rotina->colaboradores()->sync($colaboradorIds);
        }

        // Se data_inicio é hoje, gera as entradas do dia imediatamente
        if ($rotina->data_inicio->isToday()) {
            app(RotinaDiariaService::class)->gerarParaRotina($rotina, today());
        }

        return $rotina;
    }

    public function atualizar(Rotina $rotina, array $dados): Rotina
    {
        $colaboradorIds = array_key_exists('colaborador_ids', $dados)
            ? $dados['colaborador_ids']
            : false;
        unset($dados['colaborador_ids']);

        $rotina->update($dados);

        // false = não veio no payload, não altera; [] = limpa; [...] = sync
        if ($colaboradorIds !== false) {
            $rotina->colaboradores()->sync($colaboradorIds);
        }

        return $rotina->fresh();
    }

    public function desativar(Rotina $rotina): void
    {
        $rotina->update(['status' => 'inativa']);
        $rotina->delete();
    }

    public function previewProximasGeracoes(Rotina $rotina, int $quantidade = 7): array
    {
        $datas  = [];
        $cursor = today()->max($rotina->data_inicio);
        $limite = $cursor->copy()->addMonths(3); // não varrer eternamente

        while (count($datas) < $quantidade && $cursor->lte($limite)) {
            if ($rotina->data_fim && $cursor->gt($rotina->data_fim)) {
                break;
            }

            if ($this->rotinaRodaNaData($rotina, $cursor)) {
                $datas[] = [
                    'data'           => $cursor->toDateString(),
                    'dia_semana'     => $cursor->locale('pt_BR')->isoFormat('dddd'),
                    'horario'        => $rotina->horario_previsto,
                ];
            }

            $cursor->addDay();
        }

        return $datas;
    }

    public function rotinaRodaNaData(Rotina $rotina, Carbon $data): bool
    {
        if ($data->lt($rotina->data_inicio)) return false;
        if ($rotina->data_fim && $data->gt($rotina->data_fim)) return false;

        return match ($rotina->frequencia) {
            'diaria', 'turno' => true,
            'semanal'         => in_array($data->dayOfWeek, $rotina->dias_semana ?? [], true),
            'mensal'          => in_array($data->day, $rotina->dias_mes ?? [], true),
            default           => false,
        };
    }
}
