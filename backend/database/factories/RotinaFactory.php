<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\Rotina;
use App\Models\Setor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rotina>
 */
class RotinaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'empresa_id'         => Empresa::factory(),
            'setor_id'           => Setor::factory(),
            'titulo'             => fake()->sentence(3),
            'frequencia'         => 'diaria',
            'horario_previsto'   => '08:00:00',
            'foto_obrigatoria'   => false,
            'so_camera'          => true,
            'justif_obrigatoria' => false,
            'status'             => 'ativa',
            'data_inicio'        => today()->toDateString(),
        ];
    }

    public function comFoto(): static
    {
        return $this->state([
            'foto_obrigatoria' => true,
        ]);
    }

    public function comJustifObrigatoria(): static
    {
        return $this->state([
            'justif_obrigatoria' => true,
        ]);
    }

    public function inativa(): static
    {
        return $this->state(['status' => 'inativa']);
    }

    public function semanal(array $diasSemana = [1]): static
    {
        return $this->state([
            'frequencia'  => 'semanal',
            'dias_semana' => $diasSemana,
        ]);
    }
}
