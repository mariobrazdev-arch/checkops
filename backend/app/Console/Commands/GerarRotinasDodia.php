<?php

namespace App\Console\Commands;

use App\Services\RotinaDiariaService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GerarRotinasDodia extends Command
{
    protected $signature = 'rotinas:gerar {--data= : Data específica no formato YYYY-MM-DD}';
    protected $description = 'Gera registros em rotinas_diarias para o dia (padrão: hoje)';

    public function handle(RotinaDiariaService $service): int
    {
        $data = $this->option('data')
            ? Carbon::parse($this->option('data'))
            : today();

        $this->info("Gerando rotinas para {$data->toDateString()}...");

        $total = $service->gerarDoDia($data);

        $this->info("Geradas {$total} rotina(s) para {$data->toDateString()}.");

        return Command::SUCCESS;
    }
}
