# Services (API)

Módulos de acesso à API. Nunca chame axios diretamente nas páginas/stores.

## api.js — instância base
```js
// Axios com baseURL, interceptors de auth e tratamento de 401
import axios from 'axios'
const api = axios.create({ baseURL: import.meta.env.VITE_API_URL })
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})
// 401 → logout automático
```

## Módulos

### auth.service.js
```js
export const authService = {
  login: (dados) => api.post('/auth/login', dados),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
  updatePerfil: (dados) => api.put('/auth/perfil', dados),
  esqueciSenha: (email) => api.post('/auth/esqueci-senha', { email }),
}
```

### rotinas.service.js
```js
export const rotinasService = {
  // admin/gestor
  listar: (params) => api.get('/admin/rotinas', { params }),
  criar: (dados) => api.post('/admin/rotinas', dados),
  atualizar: (id, dados) => api.put(`/admin/rotinas/${id}`, dados),
  excluir: (id) => api.delete(`/admin/rotinas/${id}`),
  // colaborador
  hoje: () => api.get('/colaborador/rotinas/hoje'),
  responderSim: (id, dados) => api.post(`/colaborador/rotinas/${id}/sim`, dados),
  responderNao: (id, dados) => api.post(`/colaborador/rotinas/${id}/nao`, dados),
  historico: (params) => api.get('/colaborador/rotinas/historico', { params }),
}
```

### dashboard.service.js
```js
export const dashboardService = {
  gestor: (params) => api.get('/gestor/dashboard', { params }),
  admin: (params) => api.get('/admin/dashboard', { params }),
}
```

### usuarios.service.js
```js
export const usuariosService = {
  listar: (params) => api.get('/admin/usuarios', { params }),
  criar: (dados) => api.post('/admin/usuarios', dados),
  atualizar: (id, dados) => api.put(`/admin/usuarios/${id}`, dados),
}
```

## Padrão de chamada nas stores
```js
// store chama service, nunca axios direto
const { data } = await rotinasService.hoje()
rotinasHoje.value = data.data
```
