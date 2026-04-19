<?php

namespace App\Services;

use App\Jobs\EnviarPushNotification;
use App\Models\Alerta;
use App\Models\Auditoria;
use App\Models\RotinaDiaria;
use Illuminate\Support\Facades\Auth;

class NotificacaoService
{
    /**
     * US-20 — Envia push de rotina pendente para o colaborador.
     */
    public function pushPendente(RotinaDiaria $rd): void
    {
        $colaborador = $rd->colaborador;
        if (!$colaborador) return;

        $titulo = 'Rotina pendente';
        $corpo  = "{$rd->rotina?->titulo} vence às {$rd->rotina?->horario_previsto}";

        EnviarPushNotification::dispatch(
            $colaborador->id,
            $titulo,
            $corpo,
            '/colaborador/rotinas',
        )->onQueue('notifications');
    }

    /**
     * US-21 — Envia alerta de falha recorrente para o gestor.
     */
    public function alertaFalhaRecorrente(Alerta $alerta): void
    {
        $gestor      = $alerta->gestor;
        $colaborador = $alerta->colaborador;
        $rotina      = $alerta->rotina;

        if (!$gestor) return;

        $titulo = 'Atenção: falha recorrente';
        $corpo  = "{$colaborador?->nome} falhou {$alerta->falhas_consecutivas} vezes seguidas em {$rotina?->titulo}";

        EnviarPushNotification::dispatch(
            $gestor->id,
            $titulo,
            $corpo,
            '/gestor/alertas',
        )->onQueue('notifications');
    }
}
