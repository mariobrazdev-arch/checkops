import { ref, onUnmounted } from 'vue'

const POLLING_MS = 3000

/**
 * Polling de job assíncrono (CSV export, etc.)
 *
 * @param {Function} fetchStatus - função async que retorna { data: { status, url } }
 * @param {Function} onConcluido - callback(url) quando status === 'done'
 */
export function useJobPolling(fetchStatus, onConcluido) {
  const jobStatus  = ref(null)  // null | 'pending' | 'done'
  const jobUrl     = ref(null)
  const pollingAtivo = ref(false)

  let timer = null

  async function verificar() {
    try {
      const res = await fetchStatus()
      const payload = res.data
      jobStatus.value = payload.status

      if (payload.status === 'done') {
        jobUrl.value = payload.url
        pararPolling()
        onConcluido?.(payload.url)
      }
    } catch {
      pararPolling()
    }
  }

  function iniciar() {
    pararPolling()
    pollingAtivo.value = true
    jobStatus.value = 'pending'
    jobUrl.value = null
    timer = setInterval(verificar, POLLING_MS)
    // Primeira verificação imediata após 1s
    setTimeout(verificar, 1000)
  }

  function pararPolling() {
    if (timer) { clearInterval(timer); timer = null }
    pollingAtivo.value = false
  }

  onUnmounted(() => pararPolling())

  return { jobStatus, jobUrl, pollingAtivo, iniciar, pararPolling }
}
