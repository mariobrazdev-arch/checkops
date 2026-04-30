import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { authService } from '../services/auth.service.js'

const DESTINOS = {
  super_admin: '/super-admin/empresas',
  admin: '/admin/dashboard',
  gestor: '/gestor/dashboard',
  colaborador: '/colaborador/rotinas',
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isSuperAdmin = computed(() => user.value?.perfil === 'super_admin')
  const isAdmin = computed(() => user.value?.perfil === 'admin')
  const isGestor = computed(() => user.value?.perfil === 'gestor')
  const isColaborador = computed(() => user.value?.perfil === 'colaborador')
  const empresaId = computed(() => user.value?.empresa_id)
  const destinoPorPerfil = computed(() => DESTINOS[user.value?.perfil] ?? '/login')

  async function login(credentials, router) {
    loading.value = true
    try {
      const { data } = await authService.login(credentials)
      token.value = data.data.token
      user.value = data.data.user
      localStorage.setItem('token', data.data.token)
      if (router) {
        router.push(DESTINOS[data.data.user.perfil] ?? '/login')
      }
      return data.data.user
    } finally {
      loading.value = false
    }
  }

  async function logout(router) {
    try {
      await authService.logout()
    } catch {
      // ignora erro na requisição de logout
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('sa_empresa_id')
      localStorage.removeItem('sa_empresa_nome')
      if (router) router.push('/login')
    }
  }

  async function refreshUser() {
    if (!token.value) return
    const { data } = await authService.me()
    user.value = data.data
  }

  async function updatePerfil(dados) {
    const { data } = await authService.updatePerfil(dados)
    user.value = data.data
    return data.data
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    isSuperAdmin,
    isAdmin,
    isGestor,
    isColaborador,
    empresaId,
    destinoPorPerfil,
    login,
    logout,
    refreshUser,
    updatePerfil,
  }
})

