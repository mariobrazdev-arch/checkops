import { ref } from 'vue'
import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', () => {
  const sidebarAberta = ref(true)
  const notificacoes = ref([])
  const toasts = ref([])
  const loadingGlobal = ref(false)

  function addToast({ severity = 'info', summary, detail, life = 3000 }) {
    const id = Date.now()
    toasts.value.push({ severity, summary, detail, life, id })
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { severity, summary, detail, life } }))
  }

  function removeToast(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  function toggleSidebar() {
    sidebarAberta.value = !sidebarAberta.value
  }

  function setLoading(bool) {
    loadingGlobal.value = !!bool
  }

  return {
    sidebarAberta,
    notificacoes,
    toasts,
    loadingGlobal,
    addToast,
    removeToast,
    toggleSidebar,
    setLoading,
  }
})
