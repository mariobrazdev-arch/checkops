<?php

namespace Tests\Feature\Auth;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresa = Empresa::factory()->create();
    }

    public function test_login_com_credenciais_validas_retorna_token(): void
    {
        User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'email'      => 'admin@checkops.com',
            'password'   => Hash::make('senha123'),
            'perfil'     => 'admin',
            'status'     => 'ativo',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'admin@checkops.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_login_com_senha_errada_retorna_401(): void
    {
        User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'email'      => 'user@checkops.com',
            'password'   => Hash::make('correta'),
            'status'     => 'ativo',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@checkops.com',
            'password' => 'errada',
        ]);

        $response->assertStatus(401)
                 ->assertJsonFragment(['message' => 'Credenciais inválidas.']);
    }

    public function test_login_com_usuario_inativo_retorna_401(): void
    {
        User::factory()->inativo()->create([
            'empresa_id' => $this->empresa->id,
            'email'      => 'inativo@checkops.com',
            'password'   => Hash::make('senha123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'inativo@checkops.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(401);
    }

    public function test_colaborador_nao_acessa_rota_de_admin(): void
    {
        $colaborador = User::factory()->createOne([
            'empresa_id' => $this->empresa->id,
            'perfil'     => 'colaborador',
            'status'     => 'ativo',
        ]);
        /** @var User $colaborador */

        $response = $this->actingAs($colaborador)
                         ->getJson('/api/v1/admin/usuarios');

        $response->assertStatus(403);
    }
}
