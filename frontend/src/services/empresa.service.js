import api from './api.js'

export const empresaService = {
  buscar: () => api.get('/admin/empresa'),
  atualizar: (dados) => api.put('/admin/empresa', dados),
}
