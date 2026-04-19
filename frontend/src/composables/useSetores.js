import { ref } from 'vue'
import { setoresService } from '../services/setores.service.js'
import { useUiStore } from '../stores/ui.store.js'

export function useSetores() {
  const setores = ref([])
  const loading = ref(false)
  const salvando = ref(false)
  const removendo = ref(false)

  function uiStore() {
    return useUiStore()
  }

  async function buscar(params = {}) {
    loading.value = true
    try {
      const { data } = await setoresService.listar(params)
      setores.value = data.data
    } catch {
      uiStore().addToast({ severity: 'error', summary: 'Erro ao carregar setores', life: 3000 })
    } finally {
      loading.value = false
    }
  }

  async function salvar(dados, id = null) {
    salvando.value = true
    try {
      if (id) {
        const { data } = await setoresService.atualizar(id, dados)
        const idx = setores.value.findIndex((s) => s.id === id)
        if (idx >= 0) setores.value[idx] = data.data
      } else {
        const { data } = await setoresService.criar(dados)
        setores.value.unshift(data.data)
      }
      uiStore().addToast({ severity: 'success', summary: 'Setor salvo', life: 2500 })
      return true
    } catch (e) {
      const msg = e?.response?.data?.message ?? 'Erro ao salvar setor'
      uiStore().addToast({ severity: 'error', summary: msg, life: 3500 })
      throw e
    } finally {
      salvando.value = false
    }
  }

  async function remover(id) {
    removendo.value = true
    try {
      await setoresService.excluir(id)
      setores.value = setores.value.filter((s) => s.id !== id)
      uiStore().addToast({ severity: 'success', summary: 'Setor removido', life: 2500 })
    } catch (e) {
      const msg = e?.response?.data?.message ?? 'Erro ao remover setor'
      uiStore().addToast({ severity: 'error', summary: msg, life: 3500 })
      throw e
    } finally {
      removendo.value = false
    }
  }

  return { setores, loading, salvando, removendo, buscar, salvar, remover }
}
