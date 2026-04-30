<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory, HasUuids, AuditableTrait;

    protected $fillable = [
        'nome',
        'cnpj',
        'telefone',
        'email',
        'responsavel',
        'plano_id',
        'status',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function setores(): HasMany
    {
        return $this->hasMany(Setor::class);
    }

    public function rotinas(): HasMany
    {
        return $this->hasMany(Rotina::class);
    }
}
