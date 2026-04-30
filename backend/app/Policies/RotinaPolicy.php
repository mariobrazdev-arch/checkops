<?php

namespace App\Policies;

use App\Models\Rotina;
use App\Models\User;

class RotinaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->perfil, ['admin', 'gestor', 'super_admin']);
    }

    public function view(User $user, Rotina $rotina): bool
    {
        if (in_array($user->perfil, ['admin', 'super_admin'])) return true;
        if ($user->perfil === 'gestor') return $rotina->setor_id === $user->setor_id;
        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->perfil, ['admin', 'gestor', 'super_admin']);
    }

    public function update(User $user, Rotina $rotina): bool
    {
        if (in_array($user->perfil, ['admin', 'super_admin'])) return true;
        if ($user->perfil === 'gestor') return $rotina->setor_id === $user->setor_id;
        return false;
    }

    public function delete(User $user, Rotina $rotina): bool
    {
        if (in_array($user->perfil, ['admin', 'super_admin'])) return true;
        if ($user->perfil === 'gestor') return $rotina->setor_id === $user->setor_id;
        return false;
    }
}
