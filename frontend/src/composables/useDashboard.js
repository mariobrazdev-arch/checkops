import { ref, computed, onUnmounted } from 'vue'
import { dashboardService } from '../services/dashboard.service.js'

const POLLING_INTERVAL = 5 * 60 * 1000 // 5 minutos

export function useDashboard(perfil = 'gestor') {
  const dados      = ref(null)
  const carregando = ref(false)
  const erro       = ref(null)
  const filtroAtivo = ref('hoje')

  let timer = null

  async function buscar(filtros = {}) {
    carregando.value = true
    erro.value = null
    try {
      const params = { periodo: filtroAtivo.value, ...filtros }
      const res = perfil === 'admin'
        ? await dashboardService.admin(params)
        : await dashboardService.gestor(params)
      dados.value = res.data.data ?? res.data
    } catch (e) {
      erro.value = e?.response?.data?.message ?? 'Erro ao carregar dashboard'
    } finally {
      carregando.value = false
    }
  }

  function iniciarPolling() {
    pararPolling()
    timer = setInterval(() => buscar(), POLLING_INTERVAL)
  }

  function pararPolling() {
    if (timer) { clearInterval(timer); timer = null }
  }

  onUnmounted(() => pararPolling())

  return { dados, carregando, erro, filtroAtivo, buscar, iniciarPolling, pararPolling }
}
