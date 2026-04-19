# Models

Schema completo em `../docs/models.md`. Este arquivo cobre traits e convenções.

## Traits usadas

### AuditableTrait
Aplicar em: `Empresa`, `User`, `Setor`, `Rotina`, `RotinaDiaria`
```php
// Registra automaticamente em tabela `auditoria` nos eventos:
// created, updated, deleted
// Captura: usuario_id (auth), dados_antes, dados_depois, ip
```

### BelongsToEmpresa
```php
// Scope global: sempre filtra por empresa_id do usuário autenticado
// Evita vazamento de dados entre empresas
protected static function booted(): void
{
    static::addGlobalScope(new EmpresaScope);
}
```

## Convenções
- UUIDs como primary key em todos os models principais
- `$fillable` explícito — nunca `$guarded = []`
- Casts: `foto_lat/lng → decimal`, `dias_semana/dias_mes → array`, datas → `Carbon`
- Soft deletes: apenas em `User` e `Rotina` (histórico preservado — RN-09)

## Models e seus relacionamentos chave
```php
// User
belongsTo(Empresa, Setor, User::class /*gestor*/)
hasMany(RotinaDiaria::class, 'colaborador_id')

// Rotina
belongsTo(Empresa, Setor)
hasMany(RotinaDiaria)

// RotinaDiaria
belongsTo(Rotina)
belongsTo(User::class, 'colaborador_id')

// Setor
belongsTo(Empresa)
belongsTo(User::class, 'gestor_id')
hasMany(Rotina, User::class)
```
