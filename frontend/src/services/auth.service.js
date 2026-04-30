import api from './api.js'

export const authService = {
  login: (dados) => api.post('/auth/login', dados),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
  updatePerfil: (dados) => api.put('/auth/perfil', dados),
  uploadFotoPerfil: (base64) => api.post('/auth/perfil/foto', { foto_base64: base64 }),
  removerFotoPerfil: () => api.delete('/auth/perfil/foto'),
  esqueciSenha: (email) => api.post('/auth/esqueci-senha', { email }),
  redefinirSenha: (dados) => api.post('/auth/redefinir-senha', dados),
}
