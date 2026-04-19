# Policies

Uma Policy por Model principal. Controla autorização por perfil.

## Policies existentes

| Policy | Model | Registrada em AuthServiceProvider |
|--------|-------|----------------------------------|
| RotinaPolicy | Rotina | automático (convenção Laravel) |
| RotinaDiariaPolicy | RotinaDiaria | automático |
| UserPolicy | User | automático |
| SetorPolicy | Setor | automático |
| EmpresaPolicy | Empresa | automático |

## Regras por perfil (resumo)

| Ação | admin | gestor | colaborador |
|------|-------|--------|-------------|
| viewAny | ✓ (empresa) | ✓ (setor) | só próprias |
| view | ✓ | ✓ (setor) | só própria |
| create | ✓ | ✓ | ✗ |
| update | ✓ | ✓ (setor) | ✗ |
| delete | ✓ | ✗ | ✗ |
| reabrir (RotinaDiaria) | ✓ | ✓ (setor) | ✗ |

## Padrão de Policy
```php
class RotinaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->perfil, ['admin', 'gestor']);
    }

    public function update(User $user, Rotina $rotina): bool
    {
        if ($user->perfil === 'admin') return true;
        if ($user->perfil === 'gestor') return $rotina->setor->gestor_id === $user->id;
        return false;
    }
}
```

## Uso no Controller
```php
// Autorização via Policy — nunca if manual no controller
$this->authorize('update', $rotina); // lança 403 automaticamente
```
