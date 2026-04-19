<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Setor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Limpa dados anteriores (idempotente)
        \DB::statement('TRUNCATE TABLE rotinas_diarias, rotinas, setores, users, empresas RESTART IDENTITY CASCADE');

        // ── Super Admin ───────────────────────────────────────────────────
        User::withoutGlobalScopes()->create([
            'empresa_id' => null,
            'nome'       => 'Super Admin CheckOps',
            'email'      => 'superadmin@checkops.com',
            'password'   => Hash::make('password'),
            'perfil'     => 'super_admin',
            'status'     => 'ativo',
        ]);

        // ── Empresa Demo ──────────────────────────────────────────────────
        $empresa = Empresa::create([
            'nome'        => 'Empresa Demo',
            'cnpj'        => '00.000.000/0001-00',
            'telefone'    => '(41) 99999-0000',
            'email'       => 'contato@empresademo.com',
            'responsavel' => 'Responsável Demo',
            'status'      => 'ativo',
        ]);

        // ── Admin ─────────────────────────────────────────────────────────
        $admin = User::withoutGlobalScopes()->create([
            'empresa_id' => $empresa->id,
            'nome'       => 'Admin CheckOps',
            'email'      => 'admin@checkops.com',
            'matricula'  => 'ADM001',
            'cargo'      => 'Administrador',
            'perfil'     => 'admin',
            'status'     => 'ativo',
            'password'   => Hash::make('password'),
        ]);

        // ── Setor (gestor criado depois e vinculado) ───────────────────────
        $setor = Setor::create([
            'empresa_id' => $empresa->id,
            'gestor_id'  => $admin->id,   // provisório — atualizado abaixo
            'nome'       => 'Operações',
            'descricao'  => 'Setor de operações demo',
            'status'     => 'ativo',
        ]);

        // ── Gestor ────────────────────────────────────────────────────────
        $gestor = User::withoutGlobalScopes()->create([
            'empresa_id' => $empresa->id,
            'setor_id'   => $setor->id,
            'nome'       => 'Gestor Demo',
            'email'      => 'gestor@checkops.com',
            'matricula'  => 'GST001',
            'cargo'      => 'Gestor de Operações',
            'perfil'     => 'gestor',
            'status'     => 'ativo',
            'password'   => Hash::make('password'),
        ]);

        // Atualiza setor para apontar ao gestor correto
        $setor->update(['gestor_id' => $gestor->id]);

        // ── Colaborador ───────────────────────────────────────────────────
        User::withoutGlobalScopes()->create([
            'empresa_id' => $empresa->id,
            'setor_id'   => $setor->id,
            'gestor_id'  => $gestor->id,
            'nome'       => 'Colaborador Demo',
            'email'      => 'colaborador@checkops.com',
            'matricula'  => 'COL001',
            'cargo'      => 'Operador',
            'perfil'     => 'colaborador',
            'status'     => 'ativo',
            'password'   => Hash::make('password'),
        ]);

        $this->command->info('✓ DevSeeder concluído:');
        $this->command->info('  superadmin@checkops.com / password');
        $this->command->info('  admin@checkops.com / password');
        $this->command->info('  gestor@checkops.com / password');
        $this->command->info('  colaborador@checkops.com / password');
    }
}
