import api from './api.js'

export const validacaoService = {
  listar:  (params)              => api.get('/gestor/validacoes', { params }),
  detalhe: (id)                  => api.get(`/gestor/validacoes/${id}`),
  reabrir: (id, justificativa)   => api.post(`/gestor/validacoes/${id}/reabrir`, { justificativa }),
}
