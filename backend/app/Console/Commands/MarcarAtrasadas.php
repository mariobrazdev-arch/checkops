<?php

namespace App\Console\Commands;

use App\Services\RotinaDiariaService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarcarAtrasadas extends Command
{
    protected $signature = 'rotinas:marcar-atrasadas {--data= : Data específica no formato YYYY-MM-DD}';
    protected $description = 'Marca como atrasadas as rotinas pendentes cujo horário previsto passou há mais de 30 min';

    public function handle(RotinaDiariaService $service): int
    {
        $data = $this->option('data')
            ? Carbon::parse($this->option('data'))
            : today();

        $total = $service->marcarAtrasadas($data);

        $this->info("Marcadas {$total} rotina(s) como atrasada(s) em {$data->toDateString()}.");

        return Command::SUCCESS;
    }
}
