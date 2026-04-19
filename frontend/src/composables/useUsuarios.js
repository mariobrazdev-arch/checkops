import { ref } from 'vue'
import { usuariosService } from '../services/usuarios.service.js'
import { useAuthStore } from '../stores/auth.store.js'
import { useUiStore } from '../stores/ui.store.js'

export function useUsuarios() {
  const usuarios = ref([])
  const loading = ref(false)
  const salvando = ref(false)
  const meta = ref({ current_page: 1, last_page: 1, total: 0 })

  function uiStore() {
    return useUiStore()
  }

  async function buscar(params = {}) {
    loading.value = true
    try {
      const authStore = useAuthStore()
      const { data } = authStore.isGestor
        ? await usuariosService.listarColaboradores(params)
        : await usuariosService.listar(params)
      usuarios.value = data.data
      if (data.meta) meta.value = data.meta
    } catch {
      uiStore().addToast({ severity: 'error', summary: 'Erro ao carregar usuários', life: 3000 })
    } finally {
      loading.value = false
    }
  }

  async function salvar(dados, id = null) {
    salvando.value = true
    try {
      const authStore = useAuthStore()
      let resultado
      if (id) {
        const { data } = authStore.isGestor
          ? await usuariosService.atualizarColaborador(id, dados)
          : await usuariosService.atualizar(id, dados)
        resultado = data.data
        const idx = usuarios.value.findIndex((u) => u.id === id)
        if (idx >= 0) usuarios.value[idx] = resultado
      } else {
        const { data } = authStore.isGestor
          ? await usuariosService.criarColaborador(dados)
          : await usuariosService.criar(dados)
        resultado = data.data
        usuarios.value.unshift(resultado)
      }
      uiStore().addToast({ severity: 'success', summary: 'Usuário salvo', life: 2500 })
      return resultado
    } catch (e) {
      const erros = e?.response?.data?.errors
      const msg = erros
        ? Object.values(erros).flat().join(' ')
        : (e?.response?.data?.message ?? 'Erro ao salvar usuário')
      uiStore().addToast({ severity: 'error', summary: msg, life: 4000 })
      throw e
    } finally {
      salvando.value = false
    }
  }

  async function remover(id) {
    try {
      const authStore = useAuthStore()
      authStore.isGestor
        ? await usuariosService.excluirColaborador(id)
        : await usuariosService.excluir(id)
      usuarios.value = usuarios.value.filter((u) => u.id !== id)
      uiStore().addToast({ severity: 'success', summary: 'Usuário removido', life: 2500 })
    } catch (e) {
      const msg = e?.response?.data?.message ?? 'Erro ao remover usuário'
      uiStore().addToast({ severity: 'error', summary: msg, life: 3500 })
      throw e
    }
  }

  return { usuarios, loading, salvando, meta, buscar, salvar, remover }
}
