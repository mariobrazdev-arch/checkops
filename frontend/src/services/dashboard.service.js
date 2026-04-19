import api from './api.js'

export const dashboardService = {
  gestor: (params = {}) => api.get('/gestor/dashboard', { params }),
  admin:  (params = {}) => api.get('/admin/dashboard',  { params }),

  // US-21 — Alertas
  alertas:      ()   => api.get('/gestor/alertas'),
  marcarCiente: (id) => api.post(`/gestor/alertas/${id}/ciente`),
}
