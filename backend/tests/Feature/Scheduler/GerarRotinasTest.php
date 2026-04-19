<?php

namespace Tests\Feature\Scheduler;

use App\Models\Empresa;
use App\Models\Rotina;
use App\Models\Setor;
use App\Models\User;
use App\Services\RotinaDiariaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GerarRotinasTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private Setor $setor;
    private User $colaborador;
    private RotinaDiariaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::factory()->create();

        $gestor = User::factory()->gestor()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        $this->setor = Setor::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'gestor_id'  => $gestor->id,
            'nome'       => 'Setor Scheduler',
            'status'     => 'ativo',
        ]);

        $this->colaborador = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'setor_id'   => $this->setor->id,
            'perfil'     => 'colaborador',
            'status'     => 'ativo',
        ]);

        $this->service = app(RotinaDiariaService::class);
    }

    public function test_gerar_para_data_cria_registros_corretos(): void
    {
        $rotina = Rotina::withoutGlobalScopes()->create([
            'empresa_id'         => $this->empresa->id,
            'setor_id'           => $this->setor->id,
            'titulo'             => 'Checklist Diário',
            'frequencia'         => 'diaria',
            'horario_previsto'   => '08:00:00',
            'foto_obrigatoria'   => false,
            'so_camera'          => false,
            'justif_obrigatoria' => false,
            'status'             => 'ativa',
            'data_inicio'        => '2026-01-01',
        ]);

        $data = Carbon::parse('2026-04-18');
        $total = $this->service->gerarDoDia($data);

        $this->assertSame(1, $total);
        $this->assertDatabaseHas('rotinas_diarias', [
            'rotina_id'      => $rotina->id,
            'colaborador_id' => $this->colaborador->id,
            'data'           => '2026-04-18',
            'status'         => 'pendente',
        ]);
    }

    public function test_nao_duplica_registros_ao_gerar_mesma_data_duas_vezes(): void
    {
        Rotina::withoutGlobalScopes()->create([
            'empresa_id'         => $this->empresa->id,
            'setor_id'           => $this->setor->id,
            'titulo'             => 'Sem Duplicação',
            'frequencia'         => 'diaria',
            'horario_previsto'   => '09:00:00',
            'foto_obrigatoria'   => false,
            'so_camera'          => false,
            'justif_obrigatoria' => false,
            'status'             => 'ativa',
            'data_inicio'        => '2026-01-01',
        ]);

        $data = Carbon::parse('2026-04-18');

        $primeira = $this->service->gerarDoDia($data);
        $segunda  = $this->service->gerarDoDia($data);

        $this->assertSame(1, $primeira);
        $this->assertSame(0, $segunda);
        $this->assertDatabaseCount('rotinas_diarias', 1);
    }

    public function test_rotina_inativa_nao_gera_rotina_diaria(): void
    {
        Rotina::withoutGlobalScopes()->create([
            'empresa_id'         => $this->empresa->id,
            'setor_id'           => $this->setor->id,
            'titulo'             => 'Inativa',
            'frequencia'         => 'diaria',
            'horario_previsto'   => '09:00:00',
            'foto_obrigatoria'   => false,
            'so_camera'          => false,
            'justif_obrigatoria' => false,
            'status'             => 'inativa',
            'data_inicio'        => '2026-01-01',
        ]);

        $total = $this->service->gerarDoDia(Carbon::parse('2026-04-18'));

        $this->assertSame(0, $total);
        $this->assertDatabaseCount('rotinas_diarias', 0);
    }

    public function test_rotina_semanal_gera_apenas_no_dia_configurado(): void
    {
        Rotina::withoutGlobalScopes()->create([
            'empresa_id'         => $this->empresa->id,
            'setor_id'           => $this->setor->id,
            'titulo'             => 'Semanal Segunda',
            'frequencia'         => 'semanal',
            'dias_semana'        => [1],
            'horario_previsto'   => '10:00:00',
            'foto_obrigatoria'   => false,
            'so_camera'          => false,
            'justif_obrigatoria' => false,
            'status'             => 'ativa',
            'data_inicio'        => '2026-01-01',
        ]);

        $domingo = Carbon::parse('2026-04-19'); // dayOfWeek=0
        $segunda = Carbon::parse('2026-04-20'); // dayOfWeek=1

        $totalDomingo = $this->service->gerarDoDia($domingo);
        $totalSegunda = $this->service->gerarDoDia($segunda);

        $this->assertSame(0, $totalDomingo);
        $this->assertSame(1, $totalSegunda);
    }
}
