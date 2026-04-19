# Pages — Mapa de rotas

## Perfil: admin → LayoutAdmin
| Rota | Componente | Descrição |
|------|-----------|-----------|
| /admin/dashboard | AdminDashboard.vue | Visão geral empresa |
| /admin/empresa | AdminEmpresa.vue | Editar dados empresa |
| /admin/setores | AdminSetores.vue | CRUD setores |
| /admin/usuarios | AdminUsuarios.vue | CRUD colaboradores + gestores |
| /admin/rotinas | AdminRotinas.vue | CRUD rotinas |
| /admin/auditoria | AdminAuditoria.vue | Log auditoria |
| /admin/relatorios | AdminRelatorios.vue | Relatórios + export |

## Perfil: gestor → LayoutGestor
| Rota | Componente | Descrição |
|------|-----------|-----------|
| /gestor/dashboard | GestorDashboard.vue | Indicadores do setor |
| /gestor/colaboradores | GestorColaboradores.vue | Ver/editar colaboradores |
| /gestor/rotinas | GestorRotinas.vue | Gerenciar rotinas do setor |
| /gestor/validacao | GestorValidacao.vue | Ver fotos e justificativas |

## Perfil: colaborador → LayoutColaborador (mobile-first)
| Rota | Componente | Descrição |
|------|-----------|-----------|
| /colaborador/rotinas | ColaboradorRotinas.vue | **Tela principal** — rotinas do dia |
| /colaborador/historico | ColaboradorHistorico.vue | Histórico por período |
| /colaborador/perfil | ColaboradorPerfil.vue | Editar próprios dados |

## Compartilhado
| Rota | Componente |
|------|-----------|
| /login | Login.vue |
| /esqueci-senha | EsqueciSenha.vue |
| /redefinir-senha | RedefinirSenha.vue |
| /403 | Forbidden.vue |
| /404 | NotFound.vue |

## Regra de páginas
- Página = orquestração (busca dados, monta layout, delega a componentes)
- Lógica em composable, não inline na página
- Máximo 150 linhas por page component
