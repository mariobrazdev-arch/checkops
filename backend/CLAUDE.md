# CheckOps — Backend (Laravel 13)

## Stack
- PHP 8.3 · Laravel 13 · PostgreSQL 16 · Redis · Laravel Sanctum (JWT-like)
- Queues: Redis · Storage: S3-compatible · Scheduler: nativo Laravel

## Identidade visual
> Frontend apenas. Backend só retorna dados.

## Convenções — SEMPRE seguir

### Responses padrão
```php
// Sucesso
return response()->json(['data' => $resource, 'message' => 'OK'], 200);
// Erro validação → FormRequest (automático 422)
// Erro negócio
return response()->json(['message' => 'Motivo claro'], 422);
// Não autorizado → Policy (automático 403)
```

### Arquitetura por responsabilidade
```
Controllers  → apenas orquestram (max 20 linhas por método)
Services     → regras de negócio complexas
Policies     → autorização por perfil (admin/gestor/colaborador)
FormRequests → validação de entrada
Jobs         → operações assíncronas (email, export, notify)
Commands     → scheduler (GerarRotinas, MarcarNaoRealizadas)
Events       → disparo de side-effects (auditoria, notificações)
```

### Perfis de acesso
- `admin` → acesso irrestrito à empresa
- `gestor` → apenas setor(es) sob responsabilidade
- `colaborador` → apenas próprios dados e rotinas

### Auditoria obrigatória
Todo Model com dados críticos usa `AuditableTrait`:
```php
// Registra em tabela `auditoria`: usuario_id, acao, entidade, dados_antes, dados_depois, ip
```
Entidades auditadas: `User`, `Rotina`, `RotinaDiaria`, `Setor`, `Empresa`

### Regras de negócio críticas (nunca violar)
- RN-03: Foto APENAS via câmera — backend valida `foto_timestamp` + `foto_device_id`
- RN-05: `rotinas_diarias` imutável após fechamento — só gestor com justificativa
- RN-10: URL de foto sempre assinada temporária (1h) — nunca pública

## Estrutura de arquivos
```
app/
  Http/
    Controllers/    # 1 controller por recurso
    Middleware/     # Auth, CheckPerfil, EnsureEmpresa
    Requests/       # 1 FormRequest por ação (Store/Update)
  Models/           # Eloquent + Traits
  Services/         # Lógica de negócio (RotinaDiariaService, FotoService)
  Policies/         # 1 Policy por Model principal
  Jobs/             # GerarRelatorioCsv, EnviarPushNotification
  Console/Commands/ # GerarRotinasDodia, MarcarNaoRealizadas
  Events/           # RotinaConcluida, FalhaRecorrente
  Listeners/        # RegistrarAuditoria, NotificarGestor
database/
  migrations/       # snake_case, prefixo timestamp
  seeders/          # apenas dev/staging
routes/
  api.php           # versionado: /api/v1/
```

## Padrão de rotas
```php
// Sempre agrupado por perfil
Route::middleware(['auth:sanctum', 'perfil:admin'])->prefix('admin')->group(...)
Route::middleware(['auth:sanctum', 'perfil:gestor'])->prefix('gestor')->group(...)
Route::middleware(['auth:sanctum', 'perfil:colaborador'])->prefix('colaborador')->group(...)
```

## Models principais
Ver `docs/models.md` para schema completo.
Modelos: `Empresa`, `User`, `Setor`, `Rotina`, `RotinaDiaria`, `Auditoria`

## Scheduler (crítico)
```
00:01 → GerarRotinasDodia    (cria rotinas_diarias do dia)
HH+30 → MarcarAtrasadas      (pendentes após horário_previsto+30min)
23:55 → MarcarNaoRealizadas  (fecha pendentes/atrasadas como nao_realizada)
```
