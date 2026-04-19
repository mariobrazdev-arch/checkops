<?php

namespace App\Traits;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait AuditableTrait
{
    public static function bootAuditableTrait(): void
    {
        static::created(function ($model) {
            self::registrarAuditoria($model, 'criar', null, $model->toArray());
        });

        static::updated(function ($model) {
            self::registrarAuditoria($model, 'editar', $model->getOriginal(), $model->toArray());
        });

        static::deleted(function ($model) {
            self::registrarAuditoria($model, 'excluir', $model->toArray(), null);
        });
    }

    private static function registrarAuditoria($model, string $acao, ?array $dadosAntes, ?array $dadosDepois): void
    {
        if (! Auth::check()) {
            return;
        }

        $usuario = Auth::user();

        Auditoria::create([
            'empresa_id'   => $usuario->empresa_id,
            'usuario_id'   => $usuario->id,
            'acao'         => $acao,
            'entidade'     => class_basename($model),
            'entidade_id'  => $model->getKey(),
            'dados_antes'  => $dadosAntes,
            'dados_depois' => $dadosDepois,
            'ip'           => Request::ip() ?? '0.0.0.0',
        ]);
    }
}
