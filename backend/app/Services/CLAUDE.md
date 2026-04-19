# Services

Contêm toda lógica de negócio. Injetados nos Controllers via constructor.

## Services existentes

### RotinaService
- `listar(User $user, array $filtros)` — respeita perfil
- `criar(User $user, array $dados)` — valida frequência, datas
- `atualizar(Rotina $rotina, array $dados)`
- `desativar(Rotina $rotina)`

### RotinaDiariaService
- `listarDoDia(User $colaborador, Carbon $data)`
- `responderSim(RotinaDiaria $rd, array $dados)` — valida foto, salva metadados
- `responderNao(RotinaDiaria $rd, string $justificativa)` — valida obrigatoriedade
- `reabrir(RotinaDiaria $rd, User $gestor, string $justificativa)`
- `gerarDoDia(Carbon $data)` — chamado pelo Command

### FotoService
- `processar(UploadedFile|string $foto, array $metadados): string` — retorna URL S3
- `validarMetadados(array $metadados): void` — lança exception se inválido
- `urlTemporaria(string $path): string` — signed URL 1h

### DashboardService
- `gestor(User $gestor, array $filtros): array`
- `admin(User $admin, array $filtros): array`
- `conformidadePorPeriodo(Empresa $empresa, Carbon $inicio, Carbon $fim): array`

### NotificacaoService
- `pushPendente(RotinaDiaria $rd): void`
- `alertaFalhaRecorrente(User $gestor, array $falhas): void`

## Padrão de erro em Service
```php
// Lançar exception → Controller captura e retorna 422
throw new BusinessException('Mensagem para o usuário');
```
