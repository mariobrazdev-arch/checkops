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
    private const MAX_WIDTH  = 1280;
    private const MAX_HEIGHT = 960;
    private const JPEG_QUALITY = 72; // 0-100

    /**
     * Valida metadados obrigatórios de câmera (RN-03).
     */
    public function validarMetadados(array $metadados, ?RotinaDiaria $rd = null): void
    {
        if (empty($metadados['foto_timestamp'])) {
            throw new \InvalidArgumentException('foto_timestamp é obrigatório para fotos via câmera (RN-03).');
        }

        if (empty($metadados['foto_device_id'])) {
            throw new \InvalidArgumentException('foto_device_id é obrigatório para fotos via câmera (RN-03).');
        }

        $diferencaSegundos = abs(now()->timestamp - (int) $metadados['foto_timestamp']);
        if ($diferencaSegundos > 300) {
            $this->registrarAlerta('alerta_foto_antiga', $rd, ['diferenca_segundos' => $diferencaSegundos]);
        }

        if (empty($metadados['foto_lat']) && empty($metadados['foto_lng'])) {
            $this->registrarAlerta('sem_gps', $rd);
        }
    }

    /**
     * Processa uma foto base64: valida, redimensiona, comprime e salva no storage público.
     * Retorna o path relativo (nunca a URL — RN-10).
     */
    public function processar(string $base64, array $metadados, ?RotinaDiaria $rd = null): string
    {
        if (str_contains($base64, ',')) {
            [, $base64] = explode(',', $base64, 2);
        }

        $dados = base64_decode($base64, true);
        if ($dados === false) {
            throw new \InvalidArgumentException('Dados de imagem em base64 inválidos.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($dados);
        if (!in_array($mime, ['image/jpeg', 'image/jpg', 'image/png'], true)) {
            throw new \InvalidArgumentException("Formato inválido ({$mime}). Apenas JPEG e PNG.");
        }

        $dados = $this->redimensionarEComprimir($dados, $mime);

        $ts   = $metadados['foto_timestamp'] ?? time();
        $nome = sprintf(
            'usuarios/%s/%s/fotos/%s_%s.jpg',
            $rd?->colaborador_id ?? 'unknown',
            $rd?->rotina_id      ?? 'unknown',
            $ts,
            Str::random(8)
        );

        Storage::disk('public')->put($nome, $dados);

        return $nome;
    }

    /**
     * Processa múltiplas fotos base64, retorna array de paths.
     */
    public function processarMultiplos(array $base64List, array $metadados, ?RotinaDiaria $rd = null): array
    {
        $paths = [];
        foreach ($base64List as $base64) {
            $paths[] = $this->processar($base64, $metadados, $rd);
        }
        return $paths;
    }

    public function urlTemporaria(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    // ─── Privado ───────────────────────────────────────────────────────────────

    private function redimensionarEComprimir(string $dados, string $mime): string
    {
        $src = imagecreatefromstring($dados);
        if ($src === false) {
            return $dados; // fallback: retorna sem processar
        }

        $w = imagesx($src);
        $h = imagesy($src);

        // Calcula novo tamanho mantendo proporção
        $ratio  = min(self::MAX_WIDTH / $w, self::MAX_HEIGHT / $h, 1.0);
        $novoW  = (int) round($w * $ratio);
        $novoH  = (int) round($h * $ratio);

        if ($ratio < 1.0) {
            $dst = imagecreatetruecolor($novoW, $novoH);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $novoW, $novoH, $w, $h);
            imagedestroy($src);
        } else {
            $dst = $src;
        }

        ob_start();
        imagejpeg($dst, null, self::JPEG_QUALITY);
        $resultado = ob_get_clean();
        imagedestroy($dst);

        return $resultado;
    }

    private function registrarAlerta(string $acao, ?RotinaDiaria $rd, array $dadosDepois = []): void
    {
        if (!Auth::check()) return;

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
