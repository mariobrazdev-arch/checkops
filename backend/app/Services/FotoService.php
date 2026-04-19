<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\RotinaDiaria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FotoService
{
    /**
     * Valida metadados obrigatórios de câmera (RN-03).
     * Lança \InvalidArgumentException se foto_timestamp ou foto_device_id ausentes.
     * Registra alertas de auditoria para foto antiga (> 5 min) ou sem GPS — sem bloquear o fluxo.
     */
    public function validarMetadados(array $metadados, ?RotinaDiaria $rd = null): void
    {
        if (empty($metadados['foto_timestamp'])) {
            throw new \InvalidArgumentException('foto_timestamp é obrigatório para fotos via câmera (RN-03).');
        }

        if (empty($metadados['foto_device_id'])) {
            throw new \InvalidArgumentException('foto_device_id é obrigatório para fotos via câmera (RN-03).');
        }

        // Alerta: diferença entre foto_timestamp e now() > 5 minutos
        // foto_timestamp vem em segundos Unix do frontend (Math.floor(Date.now()/1000))
        $diferencaSegundos = abs(now()->timestamp - (int) $metadados['foto_timestamp']);
        if ($diferencaSegundos > 300) {
            $this->registrarAlerta('alerta_foto_antiga', $rd, [
                'diferenca_segundos' => $diferencaSegundos,
            ]);
        }

        // Auditoria: lat/lng nulos (GPS negado pelo usuário)
        if (empty($metadados['foto_lat']) && empty($metadados['foto_lng'])) {
            $this->registrarAlerta('sem_gps', $rd);
        }
    }

    /**
     * Processa imagem base64, valida MIME (JPEG/PNG) e salva no storage.
     * Retorna o path relativo — nunca a URL (RN-10).
     *
     * Nome: fotos/YYYY/MM/DD/rotina_{rotina_id}_{colaborador_id}_{timestamp}_{random}.jpg
     */
    public function processar(string $base64, array $metadados, ?RotinaDiaria $rd = null): string
    {
        // Extrai dados do data URI (data:image/jpeg;base64,...)
        if (str_contains($base64, ',')) {
            [, $base64] = explode(',', $base64, 2);
        }

        $dados = base64_decode($base64, true);
        if ($dados === false) {
            throw new \InvalidArgumentException('Dados de imagem em base64 inválidos.');
        }

        // Valida que é imagem JPEG ou PNG
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($dados);
        if (!in_array($mime, ['image/jpeg', 'image/jpg', 'image/png'], true)) {
            throw new \InvalidArgumentException("Formato de imagem inválido ({$mime}). Apenas JPEG e PNG são aceitos.");
        }

        $ext = $mime === 'image/png' ? 'png' : 'jpg';
        $ts  = $metadados['foto_timestamp'] ?? time();

        $nome = sprintf(
            'fotos/%s/rotina_%s_%s_%s_%s.%s',
            date('Y/m/d'),
            $rd?->rotina_id      ?? 'unknown',
            $rd?->colaborador_id ?? 'unknown',
            $ts,
            Str::random(8),
            $ext
        );

        Storage::disk(config('filesystems.default'))->put($nome, $dados);

        return $nome;
    }

    /**
     * Retorna URL assinada com expiração de 1 hora (RN-10).
     * Fallback para URL pública em ambiente local sem S3.
     */
    public function urlTemporaria(string $path): string
    {
        $disk = Storage::disk(config('filesystems.default'));

        try {
            return $disk->temporaryUrl($path, now()->addHour());
        } catch (\RuntimeException) {
            // Disco local não suporta temporaryUrl — usar URL pública (apenas dev)
            return $disk->url($path);
        }
    }

    // ─── Privado ───────────────────────────────────────────────────────────────

    private function registrarAlerta(string $acao, ?RotinaDiaria $rd, array $dadosDepois = []): void
    {
        if (!Auth::check()) {
            return;
        }

        $usuario = Auth::user();

        Auditoria::create([
            'empresa_id'   => $usuario->empresa_id,
            'usuario_id'   => $usuario->id,
            'acao'         => $acao,
            'entidade'     => 'RotinaDiaria',
            'entidade_id'  => $rd?->id,
            'dados_antes'  => null,
            'dados_depois' => $dadosDepois ?: null,
            'ip'           => Request::ip() ?? '0.0.0.0',
        ]);
    }
}
