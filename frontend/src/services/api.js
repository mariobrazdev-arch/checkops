import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Injeta Bearer token em todas as requisições
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// ── Interceptor de resposta — tratamento global de erros ─────────────────
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Sem conexão (erro de rede — sem response)
    if (!error.response) {
      _toast('error', 'Sem conexão com o servidor')
      return Promise.reject(error)
    }

    const { status, data } = error.response

    if (status === 401) {
      localStorage.removeItem('token')
      _toast('warn', 'Sessão expirada', 'Faça login novamente.')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    } else if (status === 403) {
      window.location.href = '/403'
    } else if (status === 422) {
      // Repassa para o chamador tratar — erros inline por campo
      // Não exibe toast global; cada formulário usa useFormErrors
    } else if (status === 429) {
      _toast('warn', 'Muitas tentativas, aguarde um momento')
    } else if (status >= 500) {
      console.error('[API 500]', data)
      _toast('error', 'Erro interno, tente novamente')
    }

    return Promise.reject(error)
  },
)

/**
 * Emite um toast via CustomEvent para que o App.vue possa capturar sem acoplamento circular.
 * App.vue ouve `app:toast` e delega ao useToast do PrimeVue.
 */
function _toast(severity, summary, detail) {
  window.dispatchEvent(
    new CustomEvent('app:toast', {
      detail: { severity, summary, detail },
    }),
  )
}

export default api
