<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotinaDiaria extends Model
{
    use HasFactory, HasUuids, AuditableTrait;

    protected $table = 'rotinas_diarias';

    protected $fillable = [
        'rotina_id',
        'colaborador_id',
        'data',
        'status',
        'data_hora_resposta',
        'justificativa',
        'foto_url',
        'foto_lat',
        'foto_lng',
        'foto_timestamp',
        'foto_device_id',
        'reaberta_por',
        'reaberta_justificativa',
    ];

    protected function casts(): array
    {
        return [
            'data'               => 'date',
            'data_hora_resposta' => 'datetime',
            'foto_timestamp'     => 'datetime',
            'foto_lat'           => 'decimal:8',
            'foto_lng'           => 'decimal:8',
        ];
    }

    public function rotina(): BelongsTo
    {
        return $this->belongsTo(Rotina::class);
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'colaborador_id');
    }

    public function reabertoBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reaberta_por');
    }
}
