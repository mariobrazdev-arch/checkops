<?php

namespace App\Policies;

use App\Models\Rotina;
use App\Models\User;

class RotinaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->perfil, ['admin', 'gestor']);
    }

    public function view(User $user, Rotina $rotina): bool
    {
        if ($user->perfil === 'admin') return true;
        if ($user->perfil === 'gestor') return $rotina->setor_id === $user->setor_id;
        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->perfil, ['admin', 'gestor']);
    }

    public function update(User $user, Rotina $rotina): bool
    {
        if ($user->perfil === 'admin') return true;
        if ($user->perfil === 'gestor') return $rotina->setor_id === $user->setor_id;
        return false;
    }

    public function delete(User $user, Rotina $rotina): bool
    {
        if ($user->perfil === 'admin') return true;
        if ($user->perfil === 'gestor') return $rotina->setor_id === $user->setor_id;
        return false;
    }
}
