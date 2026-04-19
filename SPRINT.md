# Sprint — Features Pendentes

## 1. Perfil [x]
**O que:** Qualquer usuário edita nome, email e senha.

**Backend**
- [x] `PUT /auth/perfil` — implementado em `PerfilController`
- [x] Validar senha atual antes de trocar

**Frontend**
- [x] `MeuPerfil.vue` — formulário nome, email, senha atual, nova senha, confirmar
- [x] Feedback de sucesso/erro inline

---

## 2. Histórico do colaborador [x]
**O que:** Colaborador vê suas rotinas respondidas com filtro de período e status.

**Backend**
- [x] `GET /colaborador/rotinas/historico` — `ColaboradorHistoricoController`
- [x] Paginação e filtros (data_inicio, data_fim, status multi-valor)

**Frontend**
- [x] `ColaboradorHistorico.vue` — lista paginada com cards expansíveis
- [x] Filtros: período (date range) + chips de status
- [x] "Carregar mais" (infinite scroll)
- [x] Detalhe ao clicar (foto, justificativa, horário)

---

## 3. Dashboard do gestor [x]
**O que:** Indicadores do setor — conformidade, atrasos, ranking de colaboradores.

**Backend**
- [x] `GET /gestor/dashboard` — `GestorDashboardController` + `DashboardService`
- [x] Resumo: total, concluidas, pendentes, atrasadas, nao_realizadas, % conformidade
- [x] Ranking colaboradores: nome + % conformidade
- [x] Rotinas críticas e justificativas frequentes

**Frontend**
- [x] `GestorDashboard.vue` — cards de métricas + gráfico barras + alertas
- [x] Filtro por período (hoje / semana / mês)
- [x] US-21: Alertas de falha recorrente com ação "Ciente"

---

## 4. Dashboard do admin [x]
**O que:** Visão geral da empresa — conformidade por setor, alertas, comparativos.

**Backend**
- [x] `GET /admin/dashboard` — `AdminDashboardController` + `DashboardService`
- [x] Resumo empresa + conformidade por setor + histórico 30 dias
- [x] Ranking setores e ranking colaboradores com falhas

**Frontend**
- [x] `AdminDashboard.vue` — cards empresa + gráfico linha + tabela setores
- [x] Filtro por período e por setor

---

## 5. Notificações push [x]
**O que:** Colaborador recebe alerta de rotina pendente; gestor recebe alerta de atraso.

**Backend**
- [x] `PushSubscriptionController` — store/destroy
- [x] Job `EnviarPushNotification` — envio via Web Push (minishlink/webpush)
- [x] Trigger: rotina pendente 30min antes → `notificacoes:enviar-pendentes` (a cada 30min)
- [x] Trigger: falha recorrente → `VerificarFalhasRecorrentes` job → notifica gestor

**Frontend**
- [x] `usePushNotifications.js` — solicitar permissão, inscrever, cancelar
- [x] `MeuPerfil.vue` — toggle de notificações push para colaboradores
- [x] Service worker registrado via VitePWA

---

## Ordem de implementação
1. **Perfil** — ✅ concluído
2. **Histórico** — ✅ concluído
3. **Dashboard gestor** — ✅ concluído
4. **Dashboard admin** — ✅ concluído
5. **Push** — ✅ concluído
