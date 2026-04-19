import api from './api.js'

export const authService = {
  login: (dados) => api.post('/auth/login', dados),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
  updatePerfil: (dados) => api.put('/auth/perfil', dados),
  esqueciSenha: (email) => api.post('/auth/esqueci-senha', { email }),
  redefinirSenha: (dados) => api.post('/auth/redefinir-senha', dados),
}
