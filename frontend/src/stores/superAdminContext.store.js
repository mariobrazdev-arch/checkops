import { ref } from 'vue'
import { defineStore } from 'pinia'

export const useSuperAdminContextStore = defineStore('superAdminContext', () => {
  const empresaId   = ref(localStorage.getItem('sa_empresa_id') ?? null)
  const empresaNome = ref(localStorage.getItem('sa_empresa_nome') ?? null)

  function entrar(id, nome) {
    empresaId.value   = id
    empresaNome.value = nome
    localStorage.setItem('sa_empresa_id', id)
    localStorage.setItem('sa_empresa_nome', nome)
  }

  function sair() {
    empresaId.value   = null
    empresaNome.value = null
    localStorage.removeItem('sa_empresa_id')
    localStorage.removeItem('sa_empresa_nome')
  }

  return { empresaId, empresaNome, entrar, sair }
})
