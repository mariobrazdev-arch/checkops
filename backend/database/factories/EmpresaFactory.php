<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
class EmpresaFactory extends Factory
{
    public function definition(): array
    {
        $num = fake()->unique()->numerify('##.###.###/####-##');

        return [
            'nome'       => fake()->company(),
            'cnpj'       => $num,
            'status'     => 'ativo',
        ];
    }
}
