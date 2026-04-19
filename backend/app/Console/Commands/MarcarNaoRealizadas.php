<?php

namespace App\Console\Commands;

use App\Services\RotinaDiariaService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarcarNaoRealizadas extends Command
{
    protected $signature = 'rotinas:fechar-dia {--data= : Data específica no formato YYYY-MM-DD}';
    protected $description = 'Fecha o dia: marca pendentes e atrasadas como nao_realizada';

    public function handle(RotinaDiariaService $service): int
    {
        $data = $this->option('data')
            ? Carbon::parse($this->option('data'))
            : today();

        $this->info("Fechando dia {$data->toDateString()}...");

        $total = $service->fecharDia($data);

        $this->info("Fechadas {$total} rotina(s) não realizadas em {$data->toDateString()}.");

        return Command::SUCCESS;
    }
}
