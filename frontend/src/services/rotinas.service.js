import api from './api.js'

export const rotinasService = {
  // admin
  listar: (params) => api.get('/admin/rotinas', { params }),
  criar: (dados) => api.post('/admin/rotinas', dados),
  atualizar: (id, dados) => api.put(`/admin/rotinas/${id}`, dados),
  excluir: (id) => api.delete(`/admin/rotinas/${id}`),
  preview: (id) => api.get(`/admin/rotinas/${id}/preview`),

  // gestor
  previewGestor: (id) => api.get(`/gestor/rotinas/${id}/preview`),
  listarGestor: (params) => api.get('/gestor/rotinas', { params }),
  criarGestor: (dados) => api.post('/gestor/rotinas', dados),
  atualizarGestor: (id, dados) => api.put(`/gestor/rotinas/${id}`, dados),
  excluirGestor: (id) => api.delete(`/gestor/rotinas/${id}`),
  reabrir: (id, dados) => api.post(`/gestor/rotinas/${id}/reabrir`, dados),

  // colaborador
  hoje: () => api.get('/colaborador/rotinas/hoje'),
  responderSim: (id, dados) => api.post(`/colaborador/rotinas/${id}/sim`, dados),
  responderNao: (id, dados) => api.post(`/colaborador/rotinas/${id}/nao`, dados),
  historico: (params) => api.get('/colaborador/rotinas/historico', { params }),
}

