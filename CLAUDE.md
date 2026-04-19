# CheckOps — Monorepo

## Projetos
```
checkops/
  backend/   → Laravel 13 API   (porta 8000)
  frontend/  → Vue 3 + PrimeVue (porta 5173)
  docs/      → contratos e decisões
```

## Leia ANTES de qualquer tarefa

| Contexto necessário | Arquivo |
|---------------------|---------|
| Stack e convenções backend | `backend/CLAUDE.md` |
| Stack, tokens de cor, estrutura frontend | `frontend/CLAUDE.md` |
| Schema de todas as tabelas | `backend/docs/models.md` |
| Contratos de API back↔front | `docs/api-contracts.md` |
| MCPs — como configurar e usar | `docs/mcps.md` |
| Controllers específicos | `backend/app/Http/Controllers/CLAUDE.md` |
| Services e regras de negócio | `backend/app/Services/CLAUDE.md` |
| Camera (componente crítico) | `frontend/src/components/camera/CLAUDE.md` |
| Stores Pinia | `frontend/src/stores/CLAUDE.md` |
| Rotas e pages | `frontend/src/pages/CLAUDE.md` |

## Regra de leitura eficiente
> Leia **apenas** o(s) CLAUDE.md relevante(s) para a tarefa atual.
> Não precisa ler todos — use a tabela acima como índice.

## Sprints planejados
- Sprint 1: Auth + Cadastros (US-01 a US-06)
- Sprint 2: Rotinas + Execução + Scheduler (US-07 a US-11)
- Sprint 3: Foto + Metadados + Histórico (US-12 a US-15)
- Sprint 4: Dashboard + Relatórios + Auditoria (US-16 a US-19)
- Sprint 5: Push + Alertas (US-20 a US-21)
- Sprint 6: Hardening + Capacitor prep

## Identidade visual (resumo)
- Fundo: `#111111` · Surface: `#1C1C1C`
- Dourado: `#C9A84C` (primary/CTA)
- Texto: `#F0F0F0`
- Nunca usar hex diretamente no Vue — sempre `var(--color-*)` ou classe utilitária

## Decisões de arquitetura
- Backend: API-only, sem SSR
- Auth: Laravel Sanctum (SPA tokens)
- Fotos: S3 + URLs assinadas 1h
- Filas: Redis
- Scheduler: Laravel nativo (cron no servidor)
- PWA agora → Capacitor na Fase 3 (sem reescrever lógica)
