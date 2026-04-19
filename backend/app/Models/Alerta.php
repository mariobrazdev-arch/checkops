<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends Model
{
    protected $table = 'alertas';

    protected $fillable = [
        'empresa_id',
        'setor_id',
        'rotina_id',
        'colaborador_id',
        'gestor_id',
        'falhas_consecutivas',
        'silenciado_ate',
    ];

    protected function casts(): array
    {
        return [
            'silenciado_ate' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function rotina(): BelongsTo
    {
        return $this->belongsTo(Rotina::class);
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'colaborador_id');
    }

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function silenciado(): bool
    {
        return $this->silenciado_ate !== null && $this->silenciado_ate->isFuture();
    }
}
