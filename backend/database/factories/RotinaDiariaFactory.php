<?php

namespace Database\Factories;

use App\Models\Rotina;
use App\Models\RotinaDiaria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RotinaDiaria>
 */
class RotinaDiariaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rotina_id'      => Rotina::factory(),
            'colaborador_id' => User::factory(),
            'data'           => today()->toDateString(),
            'status'         => 'pendente',
        ];
    }

    public function realizada(): static
    {
        return $this->state([
            'status'             => 'realizada',
            'data_hora_resposta' => now(),
        ]);
    }

    public function naoRealizada(): static
    {
        return $this->state([
            'status'             => 'nao_realizada',
            'data_hora_resposta' => now(),
        ]);
    }
}
