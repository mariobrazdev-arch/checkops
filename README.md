# CheckOps

Sistema de gestão e execução de rotinas operacionais com controle fotográfico, auditoria e notificações push.

## Estrutura do monorepo

```
checkops/
  backend/    → API Laravel 13        (porta 8000)
  frontend/   → App Vue 3 + PrimeVue  (porta 5173)
  docs/       → Contratos de API e decisões de arquitetura
```

## Perfis de acesso

| Perfil | Acesso |
|--------|--------|
| `admin` | Gestão completa da empresa (setores, usuários, rotinas, auditoria) |
| `gestor` | Gestão do próprio setor, validação e dashboard |
| `colaborador` | Execução das rotinas do dia via app mobile |

## Decisões de arquitetura

- **Auth:** Laravel Sanctum (SPA tokens)
- **Fotos:** somente via câmera nativa — S3 com URLs assinadas (1h)
- **Filas:** Redis
- **Scheduler:** Laravel nativo (cron no servidor)
- **PWA** agora → Capacitor na Fase 3 (sem reescrever lógica)

## Sprints

| Sprint | Escopo | Status |
|--------|--------|--------|
| 1 | Auth + Cadastros (US-01–06) | Concluído |
| 2 | Rotinas + Execução + Scheduler (US-07–11) | Concluído |
| 3 | Foto + Metadados + Histórico (US-12–15) | Concluído |
| 4 | Dashboard + Relatórios + Auditoria (US-16–19) | Concluído |
| 5 | Push + Alertas (US-20–21) | Concluído |
| 6 | Hardening + Capacitor prep | Em andamento |

## Rodando o projeto

```bash
# API
cd backend && php artisan serve

# App
cd frontend && npm run dev
```

Consulte os READMEs individuais para setup completo:
- [backend/README.md](backend/README.md)
- [frontend/README.md](frontend/README.md)
