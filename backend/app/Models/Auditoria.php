<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditoria';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'acao',
        'entidade',
        'entidade_id',
        'dados_antes',
        'dados_depois',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'dados_antes'  => 'array',
            'dados_depois' => 'array',
            'created_at'   => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
