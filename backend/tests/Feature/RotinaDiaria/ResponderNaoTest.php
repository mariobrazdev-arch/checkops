<?php

namespace Tests\Feature\RotinaDiaria;

use App\Models\Empresa;
use App\Models\Rotina;
use App\Models\RotinaDiaria;
use App\Models\Setor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponderNaoTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $colaborador;
    private Rotina $rotina;
    private RotinaDiaria $rd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::factory()->create();

        $gestor = User::factory()->gestor()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        $setor = Setor::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'gestor_id'  => $gestor->id,
            'nome'       => 'Setor Teste',
            'status'     => 'ativo',
        ]);

        $this->colaborador = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'perfil'     => 'colaborador',
            'setor_id'   => $setor->id,
        ]);

        $this->rotina = Rotina::withoutGlobalScopes()->create([
            'empresa_id'         => $this->empresa->id,
            'setor_id'           => $setor->id,
            'titulo'             => 'Rotina NÃO',
            'frequencia'         => 'diaria',
            'horario_previsto'   => '08:00:00',
            'foto_obrigatoria'   => false,
            'so_camera'          => false,
            'justif_obrigatoria' => true,
            'status'             => 'ativa',
            'data_inicio'        => today()->toDateString(),
        ]);

        $this->rd = RotinaDiaria::create([
            'rotina_id'      => $this->rotina->id,
            'colaborador_id' => $this->colaborador->id,
            'data'           => today()->toDateString(),
            'status'         => 'pendente',
        ]);
    }

    public function test_sem_justificativa_quando_obrigatoria_retorna_422(): void
    {
        $response = $this->actingAs($this->colaborador)
                         ->postJson("/api/v1/colaborador/rotinas/{$this->rd->id}/nao", [
                             'justificativa' => '',
                         ]);

        $response->assertStatus(422);
    }

    public function test_justificativa_com_menos_de_20_chars_retorna_422(): void
    {
        $response = $this->actingAs($this->colaborador)
                         ->postJson("/api/v1/colaborador/rotinas/{$this->rd->id}/nao", [
                             'justificativa' => 'curta demais',
                         ]);

        $response->assertStatus(422);
    }

    public function test_responder_nao_valido_atualiza_status_para_nao_realizada(): void
    {
        $response = $this->actingAs($this->colaborador)
                         ->postJson("/api/v1/colaborador/rotinas/{$this->rd->id}/nao", [
                             'justificativa' => 'Equipamento com defeito neste momento.',
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('rotinas_diarias', [
            'id'     => $this->rd->id,
            'status' => 'nao_realizada',
        ]);
    }

    public function test_responder_nao_em_rotina_ja_respondida_retorna_422(): void
    {
        $rdRespondida = RotinaDiaria::create([
            'rotina_id'          => $this->rotina->id,
            'colaborador_id'     => $this->colaborador->id,
            'data'               => today()->subDay()->toDateString(),
            'status'             => 'nao_realizada',
            'data_hora_resposta' => now()->subDay(),
            'justificativa'      => 'Já respondida anteriormente.',
        ]);

        $response = $this->actingAs($this->colaborador)
                         ->postJson("/api/v1/colaborador/rotinas/{$rdRespondida->id}/nao", [
                             'justificativa' => 'Tentativa de responder novamente.',
                         ]);

        $response->assertStatus(422);
    }
}
