import api from './api.js'

export const usuariosService = {
  // admin
  listar: (params) => api.get('/admin/usuarios', { params }),
  criar: (dados) => api.post('/admin/usuarios', dados),
  atualizar: (id, dados) => api.put(`/admin/usuarios/${id}`, dados),
  excluir: (id) => api.delete(`/admin/usuarios/${id}`),

  // gestor — apenas colaboradores do setor
  listarColaboradores: (params) => api.get('/gestor/colaboradores', { params }),
  criarColaborador: (dados) => api.post('/gestor/colaboradores', dados),
  atualizarColaborador: (id, dados) => api.put(`/gestor/colaboradores/${id}`, dados),
  excluirColaborador: (id) => api.delete(`/gestor/colaboradores/${id}`),
}
