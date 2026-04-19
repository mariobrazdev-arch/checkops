# CheckOps — API (Laravel 13)

API REST para o sistema CheckOps. Responsável por autenticação, gestão de rotinas, execução, auditoria e notificações.

## Stack

- PHP 8.3 + Laravel 13
- PostgreSQL 16
- Redis (filas e cache)
- Laravel Sanctum (autenticação SPA)
- S3-compatible (armazenamento de fotos)

## Requisitos

- PHP >= 8.3
- Composer
- PostgreSQL 16
- Redis

## Instalação

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Variáveis de ambiente relevantes

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=checkops
DB_USERNAME=
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_ENDPOINT=

SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
```

## Rodando

```bash
php artisan serve          # API em http://localhost:8000
php artisan queue:work     # Processador de filas
php artisan schedule:work  # Scheduler (dev)
```

## Scheduler

| Horário | Command | Descrição |
| ------- | ------- | --------- |
| 00:01 | `GerarRotinasDodia` | Cria as rotinas_diarias do dia |
| HH+30 | `MarcarAtrasadas` | Marca pendentes após horário previsto +30min |
| 23:55 | `MarcarNaoRealizadas` | Fecha pendentes como não realizadas |

## Estrutura

```text
app/Http/Controllers/
  Admin/        → gestão da empresa
  Gestor/       → gestão do setor
  Colaborador/  → execução de rotinas
  Shared/       → auth e perfil

app/Services/   → regras de negócio
app/Jobs/       → operações assíncronas
app/Console/    → commands do scheduler
```

## Rotas

Todas as rotas estão em `/api/v1/` e agrupadas por perfil:

```text
POST   /api/v1/login
POST   /api/v1/logout

GET    /api/v1/admin/...
GET    /api/v1/gestor/...
GET    /api/v1/colaborador/...
```

Contrato completo em [`../docs/api-contracts.md`](../docs/api-contracts.md).
