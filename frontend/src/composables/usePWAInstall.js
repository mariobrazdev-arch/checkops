import { ref, onMounted, onBeforeUnmount } from 'vue'

const STORAGE_KEY_DISMISSED = 'pwa-install-dismissed-until'

/**
 * Gerencia o fluxo de instalação do PWA.
 *
 * - Captura o evento `beforeinstallprompt` do browser
 * - Expõe `podeInstalar` (true quando o banner pode ser exibido)
 * - `instalar()` dispara o prompt nativo
 * - `dispensar()` esconde por 7 dias
 */
export function usePWAInstall() {
  const podeInstalar = ref(false)
  let deferredPrompt = null

  function jaInstalou() {
    return (
      window.matchMedia('(display-mode: standalone)').matches ||
      window.navigator.standalone === true
    )
  }

  function estaDismissed() {
    const until = localStorage.getItem(STORAGE_KEY_DISMISSED)
    if (!until) return false
    return Date.now() < Number(until)
  }

  function onBeforeInstallPrompt(event) {
    event.preventDefault()
    if (jaInstalou() || estaDismissed()) return
    deferredPrompt = event
    podeInstalar.value = true
  }

  function onAppInstalled() {
    deferredPrompt = null
    podeInstalar.value = false
  }

  async function instalar() {
    if (!deferredPrompt) return
    deferredPrompt.prompt()
    const { outcome } = await deferredPrompt.userChoice
    if (outcome === 'accepted') {
      deferredPrompt = null
      podeInstalar.value = false
    }
  }

  function dispensar() {
    const seteDias = Date.now() + 7 * 24 * 60 * 60 * 1000
    localStorage.setItem(STORAGE_KEY_DISMISSED, String(seteDias))
    podeInstalar.value = false
  }

  onMounted(() => {
    if (jaInstalou() || estaDismissed()) return
    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt)
    window.addEventListener('appinstalled', onAppInstalled)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt)
    window.removeEventListener('appinstalled', onAppInstalled)
  })

  return { podeInstalar, instalar, dispensar }
}
