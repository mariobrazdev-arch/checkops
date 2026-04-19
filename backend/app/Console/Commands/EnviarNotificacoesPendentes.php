<?php

namespace App\Console\Commands;

use App\Models\RotinaDiaria;
use App\Services\NotificacaoService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EnviarNotificacoesPendentes extends Command
{
    protected $signature   = 'notificacoes:enviar-pendentes';
    protected $description = 'Envia push para colaboradores com rotinas pendentes nos próximos 30 minutos';

    public function handle(NotificacaoService $service): int
    {
        // Rotinas cuja horario_previsto está entre agora e agora+30min
        $inicio = now();
        $fim    = now()->addMinutes(30);

        // Busca TIME do horario_previsto dentro da janela de hoje
        $pendentes = RotinaDiaria::with(['rotina', 'colaborador'])
            ->where('data', today()->toDateString())
            ->where('status', 'pendente')
            ->whereHas('rotina', function ($q) use ($inicio, $fim) {
                $q->whereTime('horario_previsto', '>=', $inicio->format('H:i'))
                  ->whereTime('horario_previsto', '<=', $fim->format('H:i'));
            })
            ->get();

        $total = 0;
        foreach ($pendentes as $rd) {
            $service->pushPendente($rd);
            $total++;
        }

        $this->info("Enviadas {$total} notificação(ões) de rotinas pendentes.");
        return Command::SUCCESS;
    }
}
