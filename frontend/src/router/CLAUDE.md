# Router

## Estrutura de rotas
```js
// Rotas agrupadas por perfil com meta.perfil e meta.layout
{
  path: '/admin',
  meta: { perfil: 'admin', layout: 'LayoutAdmin' },
  children: [ /* rotas admin */ ]
},
{
  path: '/gestor',
  meta: { perfil: 'gestor', layout: 'LayoutGestor' },
  children: [ /* rotas gestor */ ]
},
{
  path: '/colaborador',
  meta: { perfil: 'colaborador', layout: 'LayoutColaborador' },
  children: [ /* rotas colaborador */ ]
},
// Auth (sem meta.perfil)
{ path: '/login', component: Login, meta: { layout: 'LayoutAuth' } }
```

## Guards
```js
router.beforeEach((to) => {
  const auth = useAuthStore()
  // 1. Rota pública → deixa passar
  if (!to.meta.perfil) return true
  // 2. Não autenticado → login
  if (!auth.token) return '/login'
  // 3. Perfil errado → 403
  if (to.meta.perfil !== auth.user?.perfil) return '/403'
})
```

## Redirecionamento pós-login
```js
// AuthController.vue após login bem-sucedido:
const destinos = { admin: '/admin/dashboard', gestor: '/gestor/dashboard', colaborador: '/colaborador/rotinas' }
router.push(destinos[user.perfil])
```
