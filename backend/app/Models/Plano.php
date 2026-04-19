<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plano extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'nome',
        'limite_usuarios',
        'limite_setores',
        'limite_rotinas',
        'ativo',
    ];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class);
    }
}
