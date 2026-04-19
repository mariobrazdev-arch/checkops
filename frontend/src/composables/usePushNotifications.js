import { ref } from 'vue'
import api from '../services/api.js'

const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY ?? ''

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const raw = atob(base64)
  return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)))
}

export function usePushNotifications() {
  const permissao = ref(
    typeof Notification !== 'undefined' ? Notification.permission : 'default'
  )
  const inscrito   = ref(false)
  const carregando = ref(false)

  async function solicitarPermissao() {
    if (typeof Notification === 'undefined') return
    const resultado = await Notification.requestPermission()
    permissao.value = resultado
    if (resultado === 'granted') {
      await inscrever()
    }
  }

  async function inscrever() {
    if (!('serviceWorker' in navigator) || !VAPID_PUBLIC_KEY) return

    carregando.value = true
    try {
      const reg = await navigator.serviceWorker.ready
      const sub = await reg.pushManager.subscribe({
        userVisibleOnly:      true,
        applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
      })

      const { endpoint, keys } = sub.toJSON()
      await api.post('/push/subscribe', {
        endpoint,
        public_key: keys.p256dh,
        auth_token: keys.auth,
      })
      inscrito.value = true
    } catch {
      // silencia — push não disponível ou VAPID não configurado
    } finally {
      carregando.value = false
    }
  }

  async function cancelar() {
    if (!('serviceWorker' in navigator)) return
    carregando.value = true
    try {
      const reg = await navigator.serviceWorker.ready
      const sub = await reg.pushManager.getSubscription()
      if (sub) {
        await api.delete('/push/unsubscribe', { data: { endpoint: sub.endpoint } })
        await sub.unsubscribe()
      }
      inscrito.value = false
    } finally {
      carregando.value = false
    }
  }

  async function verificarEstado() {
    if (!('serviceWorker' in navigator)) return
    const reg = await navigator.serviceWorker.ready
    const sub = await reg.pushManager.getSubscription()
    inscrito.value = !!sub
    if (typeof Notification !== 'undefined') {
      permissao.value = Notification.permission
    }
  }

  return { permissao, inscrito, carregando, solicitarPermissao, inscrever, cancelar, verificarEstado }
}
