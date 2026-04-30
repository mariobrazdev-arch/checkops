<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotinaDiariaFoto extends Model
{
    use HasUuids;

    protected $table = 'rotina_diaria_fotos';

    protected $fillable = ['rotina_diaria_id', 'path', 'ordem'];

    public function rotinaDiaria(): BelongsTo
    {
        return $this->belongsTo(RotinaDiaria::class);
    }
}
