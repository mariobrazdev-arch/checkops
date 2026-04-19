<?php

namespace App\Models;

use App\Models\Scopes\EmpresaScope;
use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rotina extends Model
{
    use HasFactory, HasUuids, SoftDeletes, AuditableTrait;

    protected $fillable = [
        'empresa_id',
        'setor_id',
        'titulo',
        'descricao',
        'frequencia',
        'dias_semana',
        'dias_mes',
        'horario_previsto',
        'foto_obrigatoria',
        'so_camera',
        'justif_obrigatoria',
        'status',
        'data_inicio',
        'data_fim',
    ];

    protected function casts(): array
    {
        return [
            'dias_semana'      => 'array',
            'dias_mes'         => 'array',
            'foto_obrigatoria' => 'boolean',
            'so_camera'        => 'boolean',
            'justif_obrigatoria' => 'boolean',
            'data_inicio'      => 'date',
            'data_fim'         => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new EmpresaScope);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }

    public function rotinasDiarias(): HasMany
    {
        return $this->hasMany(RotinaDiaria::class);
    }

    public function colaboradores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rotina_colaboradores', 'rotina_id', 'colaborador_id');
    }
}
