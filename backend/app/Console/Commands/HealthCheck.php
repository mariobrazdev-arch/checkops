<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheck extends Command
{
    protected $signature = 'app:health-check';
    protected $description = 'Valida conectividade de infraestrutura e agendamentos essenciais';

    public function handle(): int
    {
        $checks = [];

        $checks[] = $this->checkDatabase();
        $checks[] = $this->checkRedis();
        $checks[] = $this->checkStorage();
        $checks[] = $this->checkScheduler();

        $this->newLine();
        $this->table(
            ['Componente', 'Status', 'Detalhe'],
            collect($checks)->map(fn (array $c) => [
                $c['component'],
                $c['ok'] ? 'OK' : 'FALHA',
                $c['message'],
            ])->all()
        );

        $failed = collect($checks)->where('ok', false)->values();

        if ($failed->isNotEmpty()) {
            $this->newLine();
            $this->error('Health-check finalizado com problemas.');
            foreach ($failed as $fail) {
                $this->line('- ' . $fail['component'] . ': ' . $fail['message']);
            }

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Health-check OK. Todos os componentes essenciais responderam.');

        return Command::SUCCESS;
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'component' => 'PostgreSQL',
                'ok' => true,
                'message' => 'Conexão com banco estabelecida.',
            ];
        } catch (\Throwable $e) {
            return [
                'component' => 'PostgreSQL',
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkRedis(): array
    {
        try {
            $pong = Redis::ping();
            $ok = in_array($pong, [true, 'PONG', '+PONG'], true);

            return [
                'component' => 'Redis',
                'ok' => $ok,
                'message' => $ok ? 'Ping respondeu com sucesso.' : 'Ping não retornou PONG.',
            ];
        } catch (\Throwable $e) {
            return [
                'component' => 'Redis',
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkStorage(): array
    {
        $file = 'health-check-' . now()->timestamp . '.tmp';

        try {
            Storage::disk('local')->put($file, 'ok');
            $exists = Storage::disk('local')->exists($file);
            Storage::disk('local')->delete($file);

            return [
                'component' => 'Storage',
                'ok' => $exists,
                'message' => $exists ? 'Leitura/escrita no disco local ok.' : 'Arquivo não encontrado após escrita.',
            ];
        } catch (\Throwable $e) {
            return [
                'component' => 'Storage',
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkScheduler(): array
    {
        try {
            $path = base_path('routes/console.php');
            if (!is_file($path)) {
                return [
                    'component' => 'Scheduler',
                    'ok' => false,
                    'message' => 'Arquivo routes/console.php não encontrado.',
                ];
            }

            $content = file_get_contents($path) ?: '';

            $required = [
                'GerarRotinasDodia::class',
                'MarcarAtrasadas::class',
                'MarcarNaoRealizadas::class',
            ];

            $missing = collect($required)
                ->filter(fn (string $item) => !str_contains($content, $item))
                ->values()
                ->all();

            if (!empty($missing)) {
                return [
                    'component' => 'Scheduler',
                    'ok' => false,
                    'message' => 'Comandos não agendados: ' . implode(', ', $missing),
                ];
            }

            return [
                'component' => 'Scheduler',
                'ok' => true,
                'message' => 'Comandos principais encontrados no agendamento.',
            ];
        } catch (\Throwable $e) {
            return [
                'component' => 'Scheduler',
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
