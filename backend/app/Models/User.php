<?php

namespace App\Models;

use App\Models\Scopes\EmpresaScope;
use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes, AuditableTrait;

    protected $fillable = [
        'empresa_id',
        'setor_id',
        'gestor_id',
        'nome',
        'email',
        'telefone',
        'cpf',
        'sexo',
        'data_nascimento',
        'foto_perfil_path',
        'matricula',
        'cargo',
        'perfil',
        'status',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'        => 'hashed',
            'data_nascimento' => 'date',
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

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function subordinados(): HasMany
    {
        return $this->hasMany(User::class, 'gestor_id');
    }

    public function rotinasDiarias(): HasMany
    {
        return $this->hasMany(RotinaDiaria::class, 'colaborador_id');
    }
}

