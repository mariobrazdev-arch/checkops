# Sprint Ativo — CheckOps

> Atualize este arquivo no início de cada sprint.
> O Claude Code lê isso para não sugerir código fora do escopo atual.

---

## Sprint atual: Sprint 4
**Objetivo:** Dashboard + Relatórios + Auditoria

## Histórias em desenvolvimento

| ID | Descrição | Status |
|----|-----------|--------|
| US-16 | Dashboard do gestor com indicadores do setor | 🔲 não iniciado |
| US-17 | Dashboard do admin com visão consolidada da empresa | 🔲 não iniciado |
| US-18 | Relatórios exportáveis (PDF/CSV) por período | 🔲 não iniciado |
| US-19 | Log de auditoria navegável para o admin | 🔲 não iniciado |

## Concluído

### Sprint 3 ✅ — 17/04/2026
**Objetivo:** Evidência fotográfica, metadados e histórico

| ID | Descrição | Status |
|----|-----------|--------|
| US-12 | Captura exclusiva por câmera (bloqueio de galeria) | ✅ concluído |
| US-13 | Registro de metadados da foto (GPS, device, timestamp) | ✅ concluído |
| US-14 | Visualização de foto e metadados pelo gestor | ✅ concluído |
| US-15 | Histórico de execuções do colaborador por período | ✅ concluído |

**Entregáveis backend:**
- `FotoService` — validarMetadados (RN-03), processar (base64→storage), urlTemporaria (signed URL 1h / fallback local)
- `RotinaDiariaResource` — foto_url via urlTemporaria, foto_timestamp, foto_device_id
- `RotinaDiariaDetalheResource` — extends Resource + colaborador + mapa_url
- `GestorValidacaoController` — index (paginado, filtros colaborador/período) + show
- `ColaboradorHistoricoController` — index paginado
- Auditoria automática: `alerta_foto_antiga` (diff > 5 min), `sem_gps`
- Rotas: `GET /gestor/validacoes`, `GET /gestor/validacoes/{id}`, `GET /colaborador/rotinas/historico`

**Entregáveis frontend:**
- `validacao.service.js` (listar, detalhe)
- `FotoViewer.vue` — foto ampliável, badge GPS, aviso URL expirando (< 5 min)
- `RotinaDetalhe.vue` — modal completo com colaborador, metadados, justificativa, FotoViewer
- `GestorValidacao.vue` — DataView com miniatura, filtros colaborador/período, abre RotinaDetalhe
- `ColaboradorHistorico.vue` — cards expansíveis, chips de status, Calendar range, "carregar mais"
- `useRotinas.js` — adicionado buscarHistorico + carregarMaisHistorico + temMais
- Router — rotas `gestor.validacao` e `colaborador.historico`

### Sprint 2 ✅ — 17/04/2026
**Objetivo:** Rotinas + Execução + Scheduler

| ID | Descrição | Status |
|----|-----------|--------|
| US-07 | CRUD de rotinas com frequência, horário e configurações | ✅ concluído |
| US-08 | Tela de rotinas do dia com status em tempo real | ✅ concluído |
| US-09 | Registrar rotina como realizada com foto obrigatória | ✅ concluído |
| US-10 | Registrar rotina como não realizada com justificativa | ✅ concluído |
| US-11 | Scheduler de geração e fechamento diário de rotinas | ✅ concluído |

**Entregáveis backend:**
- `RotinaPolicy`, `RotinaDiariaPolicy`
- `StoreRotinaRequest`, `UpdateRotinaRequest`, `ResponderSimRequest`, `ResponderNaoRequest`, `ReabrirRotinaRequest`
- `RotinaResource`, `RotinaDiariaResource`
- `RotinaService` (CRUD + preview próximas datas)
- `RotinaDiariaService` (responderSim RN-03, responderNao, reabrir, gerarDoDia, marcarAtrasadas, fecharDia)
- `FotoService` (validarMetadados, processar, urlTemporaria)
- `AdminRotinasController`, `GestorRotinasController`, `ColaboradorRotinasController`
- Commands `rotinas:gerar`, `rotinas:marcar-atrasadas`, `rotinas:fechar-dia`
- Scheduler registrado em `routes/console.php`

**Entregáveis frontend:**
- `rotinas.service.js` (admin + gestor + colaborador)
- `rotinas.store.js` (buscarHoje, responderSim, responderNao, conformidade)
- `useRotinas.js` composable
- `AdminRotinas.vue`, `GestorRotinas.vue`, `ColaboradorRotinas.vue`
- `AppBadgeStatus.vue`, `RotinaCard.vue`
- `CameraCaptura.vue` (RN-03 — getUserMedia, GPS, captura→preview→confirmar)
- `JustificativaModal.vue`
- Rotas `admin.rotinas` e `gestor.rotinas` no router

### Sprint 1 ✅
**Objetivo:** Base do sistema — autenticação, cadastros principais e estrutura de backend

| ID | Descrição | Status |
|----|-----------|--------|
| US-01 | Login por perfil com JWT e redirecionamento automático | ✅ concluído |
| US-02 | Controle de acesso por rota (middleware + guards Vue) | ✅ concluído |
| US-03 | Edição de perfil e senha pelo usuário | ✅ concluído |
| US-04 | CRUD de empresa com validação de CNPJ | ✅ concluído |
| US-05 | CRUD de setores com vínculo ao gestor | ✅ concluído |
| US-06 | CRUD de colaboradores com credenciais por e-mail | ✅ concluído |

## Fora de escopo nesta sprint — não implementar ainda
- Dashboard e relatórios (Sprint 4)
- Push notifications (Sprint 5)

## Definição de pronto (DoD) — checklist por história
- [ ] FormRequest validando todos os campos obrigatórios
- [ ] Policy bloqueando perfil errado (retorna 403)
- [ ] Auditoria registrada para ações que modificam dados
- [ ] Rota registrada em `api.php` no grupo correto de perfil
- [ ] Resource retornando apenas campos necessários
- [ ] Frontend: erro tratado com mensagem clara ao usuário
- [ ] Frontend: testado em viewport 375px (mobile) e 1280px (desktop)

---

## Como atualizar este arquivo a cada sprint

Troque a seção "Sprint atual" e mova as histórias concluídas para "Concluído":

```
## Concluído
- Sprint 1: US-01 a US-06 ✅
```

---
<!-- TEMPLATE PARA PRÓXIMAS SPRINTS — copie e cole ao avançar

## Sprint atual: Sprint 2
Objetivo: Rotinas + Execução + Scheduler

| US-07 | CRUD de rotinas com frequência, horário e configurações | 🔲 |
| US-08 | Tela de rotinas do dia com status em tempo real | 🔲 |
| US-09 | Registrar rotina como realizada com foto obrigatória | 🔲 |
| US-10 | Registrar rotina como não realizada com justificativa | 🔲 |
| US-11 | Scheduler de geração e fechamento diário de rotinas | 🔲 |

## Sprint atual: Sprint 3
Objetivo: Evidência fotográfica, metadados e histórico

| US-12 | Captura exclusiva por câmera (bloqueio de galeria) | 🔲 |
| US-13 | Registro de metadados da foto (GPS, device, timestamp) | 🔲 |
| US-14 | Visualização de foto e metadados pelo gestor | 🔲 |
| US-15 | Histórico de execuções do colaborador por período | 🔲 |

## Sprint atual: Sprint 4
Objetivo: Dashboard, relatórios e auditoria

| US-16 | Log de auditoria de ações do sistema | 🔲 |
| US-17 | Dashboard do gestor com indicadores do setor | 🔲 |
| US-18 | Dashboard do admin com visão geral da empresa | 🔲 |
| US-19 | Relatório de conformidade com exportação CSV | 🔲 |

## Sprint atual: Sprint 5
Objetivo: Notificações push e alertas

| US-20 | Notificação push de rotina pendente (PWA + e-mail) | 🔲 |
| US-21 | Alerta de falha recorrente para o gestor | 🔲 |
-->
