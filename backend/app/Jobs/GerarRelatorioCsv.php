<?php

namespace App\Jobs;

use App\Models\RotinaDiaria;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GerarRelatorioCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 2;

    public function __construct(
        private readonly array  $filtros,
        private readonly string $usuarioId,
        private readonly string $jobId,
    ) {}

    public function handle(): void
    {
        $empresaId = $this->filtros['empresa_id'];
        $path      = "relatorios/{$empresaId}/{$this->jobId}.csv";

        $csv = $this->gerarCsv();
        Storage::disk(config('filesystems.default'))->put($path, $csv);

        // URL temporária 24h
        $disk = Storage::disk(config('filesystems.default'));
        try {
            $url = $disk->temporaryUrl($path, now()->addHours(24));
        } catch (\RuntimeException) {
            $url = $disk->url($path);
        }

        Cache::put("job:{$this->jobId}:status", 'done', now()->addHour());
        Cache::put("job:{$this->jobId}:url",    $url,   now()->addHours(24));

        $usuario = User::find($this->usuarioId);
        if ($usuario) {
            EnviarEmailNotificacao::dispatch(
                $this->usuarioId,
                'Relatório de conformidade pronto',
                "Seu relatório CSV está disponível para download: {$url}",
            )->onQueue('notifications');
        }
    }

    private function gerarCsv(): string
    {
        $headers = [
            'Data', 'Rotina', 'Setor', 'Colaborador', 'Status',
            'Horario Previsto', 'Data Hora Resposta', 'Justificativa',
            'Tem Foto', 'Foto Lat', 'Foto Lng',
        ];
        $linhas = [implode(';', $headers)];

        $query = RotinaDiaria::with(['rotina.setor', 'colaborador'])
            ->whereHas('rotina', fn ($q) => $q->where('empresa_id', $this->filtros['empresa_id']));

        foreach (['setor_id', 'colaborador_id', 'rotina_id', 'status'] as $campo) {
            if (!empty($this->filtros[$campo])) {
                if ($campo === 'setor_id') {
                    $query->whereHas('rotina', fn ($q) => $q->where('setor_id', $this->filtros[$campo]));
                } elseif ($campo === 'rotina_id') {
                    $query->where('rotina_id', $this->filtros[$campo]);
                } else {
                    $query->where($campo, $this->filtros[$campo]);
                }
            }
        }
        if (!empty($this->filtros['data_inicio'])) {
            $query->whereDate('data', '>=', $this->filtros['data_inicio']);
        }
        if (!empty($this->filtros['data_fim'])) {
            $query->whereDate('data', '<=', $this->filtros['data_fim']);
        }

        $query->orderBy('data')->chunk(500, function ($registros) use (&$linhas) {
            foreach ($registros as $rd) {
                $linhas[] = implode(';', [
                    $rd->data?->toDateString() ?? '',
                    $rd->rotina?->titulo        ?? '',
                    $rd->rotina?->setor?->nome  ?? '',
                    $rd->colaborador?->nome      ?? '',
                    $rd->status,
                    $rd->rotina?->horario_previsto ?? '',
                    $rd->data_hora_resposta?->toDateTimeString() ?? '',
                    str_replace(';', ',', $rd->justificativa ?? ''),
                    $rd->foto_url ? 'sim' : 'não',
                    $rd->foto_lat ?? '',
                    $rd->foto_lng ?? '',
                ]);
            }
        });

        return implode("\n", $linhas);
    }
}
