import { ref, computed } from 'vue'
import { rotinasService } from '../services/rotinas.service.js'
import { useUiStore } from '../stores/ui.store.js'
import { useAuthStore } from '../stores/auth.store.js'

export function useRotinas() {
  const uiStore = useUiStore()
  const authStore = useAuthStore()

  const rotinas = ref([])
  const loading = ref(false)
  const salvando = ref(false)
  const preview = ref([])
  const meta = ref({})

  const isGestor = () => authStore.isGestor

  async function buscar(params = {}) {
    loading.value = true
    try {
      const fn = isGestor() ? rotinasService.listarGestor : rotinasService.listar
      const { data } = await fn(params)
      rotinas.value = data.data ?? data
      meta.value = data.meta ?? {}
    } catch {
      uiStore.addToast({ severity: 'error', summary: 'Erro ao buscar rotinas' })
    } finally {
      loading.value = false
    }
  }

  async function salvar(dados, id = null) {
    salvando.value = true
    try {
      if (id) {
        const fn = isGestor() ? rotinasService.atualizarGestor : rotinasService.atualizar
        const { data } = await fn(id, dados)
        const idx = rotinas.value.findIndex((r) => r.id === id)
        if (idx !== -1) rotinas.value[idx] = data.data
      } else {
        const fn = isGestor() ? rotinasService.criarGestor : rotinasService.criar
        const { data } = await fn(dados)
        rotinas.value.unshift(data.data)
      }
      uiStore.addToast({ severity: 'success', summary: id ? 'Rotina atualizada' : 'Rotina criada' })
      return true
    } catch (e) {
      uiStore.addToast({ severity: 'error', summary: e?.response?.data?.message ?? 'Erro ao salvar rotina' })
      return false
    } finally {
      salvando.value = false
    }
  }

  async function remover(id) {
    try {
      const fn = isGestor() ? rotinasService.excluirGestor : rotinasService.excluir
      await fn(id)
      rotinas.value = rotinas.value.filter((r) => r.id !== id)
      uiStore.addToast({ severity: 'success', summary: 'Rotina removida' })
      return true
    } catch {
      uiStore.addToast({ severity: 'error', summary: 'Erro ao remover rotina' })
      return false
    }
  }

  async function buscarPreview(id) {
    try {
      const fn = isGestor() ? rotinasService.previewGestor : rotinasService.preview
      const { data } = await fn(id)
      preview.value = data.data ?? []
    } catch {
      preview.value = []
    }
  }

  // ─── Histórico do colaborador (US-15) ────────────────────────────────────────
  const historico      = ref([])
  const carregandoMais = ref(false)
  const metaHist       = ref({})
  const paginaHist     = ref(1)
  const temMais        = computed(() => {
    if (!metaHist.value.last_page) return false
    return metaHist.value.current_page < metaHist.value.last_page
  })

  async function buscarHistorico(filtros = {}, pg = 1) {
    const ehPrimeira = pg === 1
    if (ehPrimeira) loading.value = true
    else carregandoMais.value = true

    try {
      const { data } = await rotinasService.historico({ ...filtros, page: pg })
      const novos = data.data ?? []
      historico.value    = ehPrimeira ? novos : [...historico.value, ...novos]
      metaHist.value     = data.meta ?? {}
      paginaHist.value   = pg
    } catch {
      uiStore.addToast({ severity: 'error', summary: 'Erro ao buscar histórico' })
    } finally {
      loading.value        = false
      carregandoMais.value = false
    }
  }

  async function carregarMaisHistorico(filtros = {}) {
    if (!temMais.value || carregandoMais.value) return
    await buscarHistorico(filtros, paginaHist.value + 1)
  }

  return {
    rotinas, loading, salvando, preview, meta, buscar, salvar, remover, buscarPreview,
    // histórico
    historico, carregandoMais, metaHist, temMais, buscarHistorico, carregarMaisHistorico,
  }
}
