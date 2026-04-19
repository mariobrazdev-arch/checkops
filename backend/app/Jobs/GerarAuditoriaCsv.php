<?php

namespace App\Jobs;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GerarAuditoriaCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 2;

    public function __construct(
        private readonly array  $filtros,
        private readonly string $usuarioId,
        private readonly string $jobId,
    ) {}

    public function handle(): void
    {
        $path = "relatorios/{$this->filtros['empresa_id']}/auditoria_{$this->jobId}.csv";

        $csv  = $this->montarCsv();
        Storage::disk(config('filesystems.default'))->put($path, $csv);

        // Gera URL temporária (24h)
        $disk = Storage::disk(config('filesystems.default'));
        try {
            $url = $disk->temporaryUrl($path, now()->addHours(24));
        } catch (\RuntimeException) {
            $url = $disk->url($path);
        }

        // Armazena resultado no cache para polling do frontend (1h)
        Cache::put("job:{$this->jobId}:status", 'done',  now()->addHour());
        Cache::put("job:{$this->jobId}:url",    $url,    now()->addHours(24));

        // Notifica por e-mail
        $usuario = User::find($this->usuarioId);
        if ($usuario) {
            EnviarEmailNotificacao::dispatch(
                $this->usuarioId,
                'Exportação de auditoria pronta',
                "O arquivo CSV de auditoria está disponível: {$url}",
            )->onQueue('notifications');
        }
    }

    private function montarCsv(): string
    {
        $headers = ['ID', 'Data', 'Usuário', 'Ação', 'Entidade', 'Entidade ID', 'IP'];
        $linhas  = [implode(';', $headers)];

        $query = Auditoria::with('usuario')
            ->where('empresa_id', $this->filtros['empresa_id'])
            ->orderByDesc('created_at');

        if (!empty($this->filtros['usuario_id'])) {
            $query->where('usuario_id', $this->filtros['usuario_id']);
        }
        if (!empty($this->filtros['acao'])) {
            $query->where('acao', $this->filtros['acao']);
        }
        if (!empty($this->filtros['entidade'])) {
            $query->where('entidade', $this->filtros['entidade']);
        }
        if (!empty($this->filtros['data_inicio'])) {
            $query->whereDate('created_at', '>=', $this->filtros['data_inicio']);
        }
        if (!empty($this->filtros['data_fim'])) {
            $query->whereDate('created_at', '<=', $this->filtros['data_fim']);
        }

        $query->chunk(500, function ($registros) use (&$linhas) {
            foreach ($registros as $r) {
                $linhas[] = implode(';', [
                    $r->id,
                    $r->created_at?->toDateTimeString() ?? '',
                    $r->usuario?->nome ?? '',
                    $r->acao,
                    $r->entidade ?? '',
                    $r->entidade_id ?? '',
                    $r->ip,
                ]);
            }
        });

        return implode("\n", $linhas);
    }
}
