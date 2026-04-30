<?php

namespace App\Policies;

use App\Models\RotinaDiaria;
use App\Models\User;

class RotinaDiariaPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // colaborador vê as próprias; gestor/admin filtrado no service
    }

    public function view(User $user, RotinaDiaria $rotinaDiaria): bool
    {
        if (in_array($user->perfil, ['admin', 'super_admin'])) return true;
        if ($user->perfil === 'gestor') {
            return $rotinaDiaria->rotina->setor_id === $user->setor_id;
        }
        return $rotinaDiaria->colaborador_id === $user->id;
    }

    public function responder(User $user, RotinaDiaria $rotinaDiaria): bool
    {
        return $user->perfil === 'colaborador'
            && $rotinaDiaria->colaborador_id === $user->id;
    }

    public function reabrir(User $user, RotinaDiaria $rotinaDiaria): bool
    {
        if (in_array($user->perfil, ['admin', 'super_admin'])) return true;
        if ($user->perfil === 'gestor') {
            return $rotinaDiaria->rotina->setor_id === $user->setor_id;
        }
        return false;
    }
}
