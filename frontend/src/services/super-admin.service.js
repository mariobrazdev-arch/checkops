import api from './api.js'

export const superAdminEmpresasService = {
  listar: (params) => api.get('/super-admin/empresas', { params }),
  criar:  (dados)  => api.post('/super-admin/empresas', dados),
  buscar: (id)     => api.get(`/super-admin/empresas/${id}`),
  atualizar: (id, dados) => api.put(`/super-admin/empresas/${id}`, dados),
  excluir: (id)    => api.delete(`/super-admin/empresas/${id}`),
}

export const superAdminUsuariosService = {
  listar: (params) => api.get('/super-admin/usuarios', { params }),
  criar:  (dados)  => api.post('/super-admin/usuarios', dados),
  atualizar: (id, dados) => api.put(`/super-admin/usuarios/${id}`, dados),
  excluir: (id)    => api.delete(`/super-admin/usuarios/${id}`),
}

export const superAdminPlanosService = {
  listar: (params) => api.get('/super-admin/planos', { params }),
  criar:  (dados)  => api.post('/super-admin/planos', dados),
  atualizar: (id, dados) => api.put(`/super-admin/planos/${id}`, dados),
  excluir: (id)    => api.delete(`/super-admin/planos/${id}`),
}
