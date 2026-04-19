<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nome'       => fake()->name(),
            'email'      => fake()->unique()->safeEmail(),
            'password'   => static::$password ??= Hash::make('password'),
            'perfil'     => 'colaborador',
            'status'     => 'ativo',
        ];
    }

    public function admin(): static
    {
        return $this->state(['perfil' => 'admin']);
    }

    public function gestor(): static
    {
        return $this->state(['perfil' => 'gestor']);
    }

    public function inativo(): static
    {
        return $this->state(['status' => 'inativo']);
    }
}

