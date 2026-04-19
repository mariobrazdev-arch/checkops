<?php

namespace Tests\Feature\Auditoria;

use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\Rotina;
use App\Models\Setor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $admin;
    private Setor $setor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::factory()->create();

        $this->admin = User::factory()->admin()->create([
            'empresa_id' => $this->empresa->id,
            'status'     => 'ativo',
        ]);

        $gestor = User::factory()->gestor()->create([
            'empresa_id' => $this->empresa->id,
            'status'     => 'ativo',
        ]);

        $this->setor = Setor::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'gestor_id'  => $gestor->id,
            'nome'       => 'Setor Auditoria',
            'status'     => 'ativo',
        ]);
    }

    public function test_criar_rotina_autenticado_registra_auditoria(): void
    {
        $this->actingAs($this->admin)
             ->postJson('/api/v1/admin/rotinas', [
                 'titulo'             => 'Rotina Auditada',
                 'descricao'          => 'Rotina para validar auditoria',
                 'setor_id'           => $this->setor->id,
                 'frequencia'         => 'diaria',
                 'horario_previsto'   => '08:00',
                 'foto_obrigatoria'   => false,
                 'so_camera'          => false,
                 'justif_obrigatoria' => false,
                 'data_inicio'        => now()->toDateString(),
             ])
             ->assertStatus(201);

        $this->assertDatabaseHas('auditoria', [
            'empresa_id' => $this->empresa->id,
            'usuario_id' => $this->admin->id,
            'acao'       => 'criar',
            'entidade'   => 'Rotina',
        ]);
    }

    public function test_editar_usuario_registra_dados_antes_e_depois(): void
    {
        $alvo = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'perfil'     => 'colaborador',
            'nome'       => 'Nome Antigo',
            'status'     => 'ativo',
        ]);

        $this->actingAs($this->admin);

        $alvo->update([
            'nome' => 'Nome Novo',
        ]);

        $auditoria = Auditoria::query()
            ->where('entidade', 'User')
            ->where('entidade_id', $alvo->id)
            ->where('acao', 'editar')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditoria);
        $this->assertSame('Nome Antigo', $auditoria->dados_antes['nome'] ?? null);
        $this->assertSame('Nome Novo', $auditoria->dados_depois['nome'] ?? null);
    }

    public function test_colaborador_nao_acessa_rota_admin_de_auditoria(): void
    {
        $colaborador = User::factory()->createOne([
            'empresa_id' => $this->empresa->id,
            'perfil'     => 'colaborador',
            'status'     => 'ativo',
        ]);
        /** @var User $colaborador */

        $this->actingAs($colaborador)
             ->getJson('/api/v1/admin/auditoria')
             ->assertStatus(403);
    }

    public function test_auditoria_nao_pode_ser_editada_ou_deletada(): void
    {
        $this->actingAs($this->admin)
             ->putJson('/api/v1/admin/auditoria', [
                 'acao' => 'editar',
             ])
             ->assertStatus(405);

        $this->actingAs($this->admin)
             ->deleteJson('/api/v1/admin/auditoria')
             ->assertStatus(405);
    }
}
