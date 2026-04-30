import api from './api.js'

export const relatoriosService = {
  listar:   (params = {}) => api.get('/admin/relatorios',          { params }),
  resumo:   (params = {}) => api.get('/admin/relatorios/resumo',   { params }),
  exportar: (params = {}) => api.post('/admin/relatorios/exportar', params),
  status:   (jobId)       => api.get(`/admin/relatorios/status/${jobId}`),
}
