<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@checkops.com');
        $senha = env('SUPER_ADMIN_PASSWORD', 'superadmin123');

        if (User::withoutGlobalScopes()->where('email', $email)->exists()) {
            $this->command->info("Super admin já existe: {$email}");
            return;
        }

        User::withoutGlobalScopes()->create([
            'empresa_id' => null,
            'nome'       => 'Super Admin CheckOps',
            'email'      => $email,
            'password'   => Hash::make($senha),
            'perfil'     => 'super_admin',
            'status'     => 'ativo',
        ]);

        $this->command->info("✓ Super admin criado: {$email} / {$senha}");
    }
}
