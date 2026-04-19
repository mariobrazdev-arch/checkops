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
        if ($user->perfil === 'admin') return true;
        if ($user->perfil === 'gestor') {
            return $rotinaDiaria->rotina->setor_id === $user->setor_id;
        }
        // colaborador: apenas a própria
        return $rotinaDiaria->colaborador_id === $user->id;
    }

    public function responder(User $user, RotinaDiaria $rotinaDiaria): bool
    {
        return $user->perfil === 'colaborador'
            && $rotinaDiaria->colaborador_id === $user->id;
    }

    public function reabrir(User $user, RotinaDiaria $rotinaDiaria): bool
    {
        if ($user->perfil === 'admin') return true;
        if ($user->perfil === 'gestor') {
            return $rotinaDiaria->rotina->setor_id === $user->setor_id;
        }
        return false;
    }
}
