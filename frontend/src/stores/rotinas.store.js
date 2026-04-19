import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { rotinasService } from '../services/rotinas.service.js'

export const useRotinasStore = defineStore('rotinas', () => {
  const rotinasHoje = ref([])
  const historico = ref([])
  const carregando = ref(false)
  const erro = ref(null)

  async function buscarHoje() {
    carregando.value = true
    erro.value = null
    try {
      const { data } = await rotinasService.hoje()
      rotinasHoje.value = data.data ?? data
    } catch (e) {
      erro.value = e?.response?.data?.message ?? 'Erro ao buscar rotinas'
    } finally {
      carregando.value = false
    }
  }

  async function responderSim(id, dados) {
    const { data } = await rotinasService.responderSim(id, dados)
    _atualizarRotina(id, data.data ?? data)
    return data
  }

  async function responderNao(id, dados) {
    const { data } = await rotinasService.responderNao(id, dados)
    _atualizarRotina(id, data.data ?? data)
    return data
  }

  async function buscarHistorico(params) {
    carregando.value = true
    try {
      const { data } = await rotinasService.historico(params)
      historico.value = data
    } finally {
      carregando.value = false
    }
  }

  function _atualizarRotina(id, rotina) {
    const idx = rotinasHoje.value.findIndex((r) => r.id === id)
    if (idx !== -1) rotinasHoje.value[idx] = rotina
  }

  function $reset() {
    rotinasHoje.value = []
    historico.value = []
    carregando.value = false
    erro.value = null
  }

  const conformidade = computed(() => {
    const total = rotinasHoje.value.length
    const concluidas = rotinasHoje.value.filter((r) => r.status === 'realizada').length
    const percentual = total > 0 ? Math.round((concluidas / total) * 100) : 0
    return { total, concluidas, percentual }
  })

  return {
    rotinasHoje,
    historico,
    carregando,
    erro,
    conformidade,
    buscarHoje,
    responderSim,
    responderNao,
    buscarHistorico,
    $reset,
  }
})
