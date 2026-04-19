<?php

namespace App\Models;

use App\Models\Scopes\EmpresaScope;
use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setor extends Model
{
    use HasFactory, HasUuids, AuditableTrait;

    protected $table = 'setores';

    protected $fillable = [
        'empresa_id',
        'gestor_id',
        'nome',
        'descricao',
        'status',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new EmpresaScope);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function rotinas(): HasMany
    {
        return $this->hasMany(Rotina::class);
    }

    public function colaboradores(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
