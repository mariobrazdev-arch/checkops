<?php

namespace Tests\Feature\RotinaDiaria;

use App\Models\Empresa;
use App\Models\Rotina;
use App\Models\RotinaDiaria;
use App\Models\Setor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponderSimTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $gestor;
    private Setor $setor;
    private User $colaborador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::factory()->create();

        $this->gestor = User::factory()->gestor()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        $this->setor = Setor::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'gestor_id'  => $this->gestor->id,
            'nome'       => 'Setor Teste',
            'status'     => 'ativo',
        ]);

        $this->colaborador = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'perfil'     => 'colaborador',
            'setor_id'   => $this->setor->id,
        ]);
    }

    public function test_responder_sim_sem_foto_em_rotina_com_foto_obrigatoria_retorna_422(): void
    {
        $rotina = Rotina::withoutGlobalScopes()->create([
            'empresa_id'       => $this->empresa->id,
            'setor_id'         => $this->setor->id,
            'titulo'           => 'Rotina Foto',
            'frequencia'       => 'diaria',
            'horario_previsto' => '08:00:00',
            'foto_obrigatoria' => true,
            'so_camera'        => true,
            'justif_obrigatoria' => false,
            'status'           => 'ativa',
            'data_inicio'      => today()->toDateString(),
        ]);

        $rd = RotinaDiaria::create([
            'rotina_id'      => $rotina->id,
            'colaborador_id' => $this->colaborador->id,
            'data'           => today()->toDateString(),
            'status'         => 'pendente',
        ]);

        $response = $this->actingAs($this->colaborador)
                         ->postJson("/api/v1/colaborador/rotinas/{$rd->id}/sim", []);

        $response->assertStatus(422);
    }

    public function test_responder_sim_em_rotina_ja_realizada_retorna_422(): void
    {
        $rotina = Rotina::withoutGlobalScopes()->create([
            'empresa_id'         => $this->empresa->id,
            'setor_id'           => $this->setor->id,
            'titulo'             => 'Rotina Já Feita',
            'frequencia'         => 'diaria',
            'horario_previsto'   => '08:00:00',
            'foto_obrigatoria'   => false,
            'so_camera'          => false,
            'justif_obrigatoria' => false,
            'status'             => 'ativa',
            'data_inicio'        => today()->toDateString(),
        ]);

        $rd = RotinaDiaria::create([
            'rotina_id'          => $rotina->id,
            'colaborador_id'     => $this->colaborador->id,
            'data'               => today()->toDateString(),
            'status'             => 'realizada',
            'data_hora_resposta' => now(),
        ]);

        $response = $this->actingAs($this->colaborador)
                         ->postJson("/api/v1/colaborador/rotinas/{$rd->id}/sim", []);

        $response->assertStatus(422);
    }

    public function test_responder_sim_valido_sem_foto_obrigatoria_atualiza_status(): void
    {
        $rotina = Rotina::withoutGlobalScopes()->create([
            'empresa_id'         => $this->empresa->id,
            'setor_id'           => $this->setor->id,
            'titulo'             => 'Rotina Simples',
            'frequencia'         => 'diaria',
            'horario_previsto'   => '08:00:00',
            'foto_obrigatoria'   => false,
            'so_camera'          => false,
            'justif_obrigatoria' => false,
            'status'             => 'ativa',
            'data_inicio'        => today()->toDateString(),
        ]);

        $rd = RotinaDiaria::create([
            'rotina_id'      => $rotina->id,
            'colaborador_id' => $this->colaborador->id,
            'data'           => today()->toDateString(),
            'status'         => 'pendente',
        ]);

        $response = $this->actingAs($this->colaborador)
                         ->postJson("/api/v1/colaborador/rotinas/{$rd->id}/sim", []);

        $response->assertStatus(200);
        $this->assertDatabaseHas('rotinas_diarias', [
            'id'     => $rd->id,
            'status' => 'realizada',
        ]);
    }

    public function test_data_hora_resposta_e_definida_pelo_servidor(): void
    {
        $rotina = Rotina::withoutGlobalScopes()->create([
            'empresa_id'         => $this->empresa->id,
            'setor_id'           => $this->setor->id,
            'titulo'             => 'Rotina Timestamp',
            'frequencia'         => 'diaria',
            'horario_previsto'   => '08:00:00',
            'foto_obrigatoria'   => false,
            'so_camera'          => false,
            'justif_obrigatoria' => false,
            'status'             => 'ativa',
            'data_inicio'        => today()->toDateString(),
        ]);

        $rd = RotinaDiaria::create([
            'rotina_id'      => $rotina->id,
            'colaborador_id' => $this->colaborador->id,
            'data'           => today()->toDateString(),
            'status'         => 'pendente',
        ]);

        $antes = now()->subSecond();

        $this->actingAs($this->colaborador)
             ->postJson("/api/v1/colaborador/rotinas/{$rd->id}/sim", []);

        $depois = now()->addSecond();

        $rd->refresh();
        $this->assertNotNull($rd->data_hora_resposta);
        $this->assertTrue($rd->data_hora_resposta->between($antes, $depois));
    }
}
