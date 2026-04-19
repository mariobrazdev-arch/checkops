<?php

namespace App\Jobs;

use App\Models\Alerta;
use App\Models\Auditoria;
use App\Models\RotinaDiaria;
use App\Services\NotificacaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class VerificarFalhasRecorrentes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    private const LIMITE_FALHAS = 3;

    public function __construct(
        private readonly string $rotinaId,
        private readonly string $colaboradorId,
    ) {}

    public function handle(NotificacaoService $notificacaoService): void
    {
        // Busca os últimos N registros desta rotina para este colaborador, mais recentes primeiro
        $ultimas = RotinaDiaria::where('rotina_id', $this->rotinaId)
            ->where('colaborador_id', $this->colaboradorId)
            ->orderByDesc('data')
            ->limit(self::LIMITE_FALHAS)
            ->get(['status', 'rotina_id', 'colaborador_id']);

        if ($ultimas->count() < self::LIMITE_FALHAS) return;

        // Verifica se TODAS as últimas N são falhas (nao_realizada)
        $todasFalhas = $ultimas->every(fn ($r) => $r->status === 'nao_realizada');
        if (!$todasFalhas) return;

        // Busca rotina e colaborador
        $rd = $ultimas->first();
        $rotina      = \App\Models\Rotina::with('setor.gestor')->find($this->rotinaId);
        $colaborador = \App\Models\User::find($this->colaboradorId);
        $gestor      = $rotina?->setor?->gestor;

        if (!$rotina || !$colaborador || !$gestor) return;

        // Cria ou atualiza alerta
        $alerta = Alerta::updateOrCreate(
            [
                'rotina_id'      => $this->rotinaId,
                'colaborador_id' => $this->colaboradorId,
            ],
            [
                'empresa_id'           => $rotina->empresa_id,
                'setor_id'             => $rotina->setor_id,
                'gestor_id'            => $gestor->id,
                'falhas_consecutivas'  => self::LIMITE_FALHAS,
                'silenciado_ate'       => null, // reinicia silêncio ao ter nova falha
            ],
        );

        $alerta->load(['rotina', 'colaborador', 'gestor']);

        // Registra auditoria
        Auditoria::create([
            'empresa_id'   => $rotina->empresa_id,
            'usuario_id'   => $gestor->id,
            'acao'         => 'alerta_falha_recorrente',
            'entidade'     => 'Alerta',
            'entidade_id'  => $alerta->id,
            'dados_depois' => [
                'rotina_id'      => $this->rotinaId,
                'colaborador_id' => $this->colaboradorId,
                'falhas'         => self::LIMITE_FALHAS,
            ],
            'ip'           => '0.0.0.0',
        ]);

        // Envia notificação ao gestor
        $notificacaoService->alertaFalhaRecorrente($alerta);
    }
}
