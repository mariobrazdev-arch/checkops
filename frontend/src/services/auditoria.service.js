import api from './api.js'

export const auditoriaService = {
  listar:   (params = {}) => api.get('/admin/auditoria',          { params }),
  exportar: (params = {}) => api.get('/admin/auditoria/exportar', { params }),
  status:   (jobId)       => api.get(`/admin/relatorios/status/${jobId}`),
}
