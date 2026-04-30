import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.store.js'
import { useSuperAdminContextStore } from '../stores/superAdminContext.store.js'

// Páginas — lazy-loaded
const LoginPage           = () => import('../pages/auth/LoginPage.vue')
const EsqueciSenha        = () => import('../pages/auth/EsqueciSenha.vue')
const MeuPerfil           = () => import('../pages/shared/MeuPerfil.vue')

const AdminDashboard      = () => import('../pages/admin/AdminDashboard.vue')
const AdminEmpresa        = () => import('../pages/admin/AdminEmpresa.vue')
const AdminSetores        = () => import('../pages/admin/AdminSetores.vue')
const AdminUsuarios       = () => import('../pages/admin/AdminUsuarios.vue')
const AdminRotinas        = () => import('../pages/admin/AdminRotinas.vue')
const AdminAuditoria         = () => import('../pages/admin/AdminAuditoria.vue')
const AdminRelatorios        = () => import('../pages/admin/AdminRelatorios.vue')
const AdminAcompanhamento    = () => import('../pages/admin/AdminAcompanhamento.vue')
const AdminValidacao         = () => import('../pages/admin/AdminValidacao.vue')

const GestorDashboard     = () => import('../pages/gestor/GestorDashboard.vue')
const GestorUsuarios      = () => import('../pages/admin/AdminUsuarios.vue') // reusa AdminUsuarios
const GestorRotinas       = () => import('../pages/gestor/GestorRotinas.vue')
const GestorValidacao        = () => import('../pages/gestor/GestorValidacao.vue')
const GestorAcompanhamento   = () => import('../pages/gestor/GestorAcompanhamento.vue')

const ColaboradorRotinas  = () => import('../pages/colaborador/ColaboradorRotinas.vue')
const ColaboradorHistorico = () => import('../pages/colaborador/ColaboradorHistorico.vue')

const SuperAdminEmpresas  = () => import('../pages/super-admin/SuperAdminEmpresas.vue')
const SuperAdminUsuarios  = () => import('../pages/super-admin/SuperAdminUsuarios.vue')
const SuperAdminPlanos    = () => import('../pages/super-admin/SuperAdminPlanos.vue')

const Forbidden           = () => import('../pages/errors/Forbidden.vue')
const Page404             = () => import('../pages/Page404.vue')

const routes = [
  // Auth — público
  {
    path: '/login',
    name: 'login',
    component: LoginPage,
    meta: { layout: 'LayoutAuth' },
  },
  {
    path: '/esqueci-senha',
    name: 'esqueci-senha',
    component: EsqueciSenha,
    meta: { layout: 'LayoutAuth' },
  },

  // Super Admin
  {
    path: '/super-admin',
    meta: { perfil: 'super_admin', layout: 'LayoutSuperAdmin' },
    children: [
      { path: 'empresas', name: 'super-admin.empresas', component: SuperAdminEmpresas },
      { path: 'usuarios', name: 'super-admin.usuarios', component: SuperAdminUsuarios },
      { path: 'planos',   name: 'super-admin.planos',   component: SuperAdminPlanos },
    ],
  },

  // Admin
  {
    path: '/admin',
    meta: { perfil: 'admin', layout: 'LayoutAdmin' },
    children: [
      { path: 'dashboard',  name: 'admin.dashboard',  component: AdminDashboard },
      { path: 'empresa',    name: 'admin.empresa',    component: AdminEmpresa },
      { path: 'setores',    name: 'admin.setores',    component: AdminSetores },
      { path: 'usuarios',   name: 'admin.usuarios',   component: AdminUsuarios },
      { path: 'rotinas',    name: 'admin.rotinas',    component: AdminRotinas },
      { path: 'auditoria',  name: 'admin.auditoria',  component: AdminAuditoria  },
      { path: 'relatorios',     name: 'admin.relatorios',     component: AdminRelatorios },
      { path: 'acompanhamento', name: 'admin.acompanhamento', component: AdminAcompanhamento },
      { path: 'validacao',      name: 'admin.validacao',      component: AdminValidacao },
      { path: 'perfil',         name: 'admin.perfil',         component: MeuPerfil },
      // Meu Setor — admin com setor_id atribuído opera como gestor
      { path: 'meu-setor/acompanhamento', name: 'admin.meu-setor.acompanhamento', component: GestorAcompanhamento },
      { path: 'meu-setor/validacao',      name: 'admin.meu-setor.validacao',      component: GestorValidacao },
    ],
  },

  // Gestor
  {
    path: '/gestor',
    meta: { perfil: 'gestor', layout: 'LayoutGestor' },
    children: [
      { path: 'dashboard',      name: 'gestor.dashboard',      component: GestorDashboard },
      { path: 'colaboradores',  name: 'gestor.colaboradores',  component: GestorUsuarios },
      { path: 'rotinas',    name: 'gestor.rotinas',    component: GestorRotinas },
      { path: 'acompanhamento', name: 'gestor.acompanhamento', component: GestorAcompanhamento },
      { path: 'validacao',  name: 'gestor.validacao',  component: GestorValidacao },
      { path: 'perfil',     name: 'gestor.perfil',     component: MeuPerfil },
    ],
  },

  // Colaborador
  {
    path: '/colaborador',
    meta: { perfil: 'colaborador', layout: 'LayoutColaborador' },
    children: [
      { path: 'rotinas',   name: 'colaborador.rotinas',   component: ColaboradorRotinas },
      { path: 'historico', name: 'colaborador.historico', component: ColaboradorHistorico },
      { path: 'perfil',    name: 'colaborador.perfil',    component: MeuPerfil },
    ],
  },

  // Erros
  { path: '/403', name: '403', component: Forbidden, meta: { layout: 'LayoutAuth' } },
  { path: '/404', name: '404', component: Page404,   meta: { layout: 'LayoutAuth' } },

  // Raiz
  { path: '/', redirect: '/login' },

  // Catch-all
  { path: '/:pathMatch(.*)*', redirect: '/404' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Guard global
router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  // Rota pública (sem meta.perfil) → deixa passar
  if (!to.meta.perfil) return true

  // Não autenticado → login
  if (!authStore.token) return '/login'

  // Carrega user se ainda não está no state (ex: após reload de página)
  if (!authStore.user) {
    try {
      await authStore.refreshUser()
    } catch {
      // token inválido / expirado
      authStore.token = null
      localStorage.removeItem('token')
      return '/login'
    }
  }

  // Super admin pode acessar rotas /admin quando tem contexto de empresa ativo
  if (authStore.user?.perfil === 'super_admin' && to.path.startsWith('/admin')) {
    const ctxStore = useSuperAdminContextStore()
    if (!ctxStore.empresaId) return '/super-admin/empresas'
    return true
  }

  // Perfil errado → 403
  const perfisPermitidos = Array.isArray(to.meta.perfil) ? to.meta.perfil : [to.meta.perfil]
  if (!perfisPermitidos.includes(authStore.user?.perfil)) return '/403'

  return true
})

export default router

