<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\Setor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setor>
 */
class SetorFactory extends Factory
{
    public function definition(): array
    {
        $empresa = Empresa::factory()->create();
        $gestor  = User::factory()->gestor()->create(['empresa_id' => $empresa->id]);

        return [
            'empresa_id' => $empresa->id,
            'gestor_id'  => $gestor->id,
            'nome'       => fake()->word() . ' Setor',
            'status'     => 'ativo',
        ];
    }
}
