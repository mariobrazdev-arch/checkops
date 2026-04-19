import api from './api.js'

export const setoresService = {
  listar: (params) => api.get('/admin/setores', { params }),
  criar: (dados) => api.post('/admin/setores', dados),
  atualizar: (id, dados) => api.put(`/admin/setores/${id}`, dados),
  excluir: (id) => api.delete(`/admin/setores/${id}`),
}
