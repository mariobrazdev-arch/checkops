# Stores (Pinia)

## Stores existentes

### auth.store.js
Estado: `user`, `token`, `loading`
Actions: `login`, `logout`, `refreshUser`, `updatePerfil`
Getters: `isAdmin`, `isGestor`, `isColaborador`, `empresaId`
Persiste: `token` no localStorage

### rotinas.store.js
Estado: `rotinasHoje`, `historico`, `carregando`, `erro`
Actions: `buscarHoje`, `responderSim`, `responderNao`, `buscarHistorico`
Reset no logout

### ui.store.js
Estado: `sidebarAberta`, `notificacoes[]`, `toasts[]`
Actions: `addToast`, `removeToast`, `toggleSidebar`

## Padrão de toast
```js
// Sempre via uiStore — nunca direto no componente
uiStore.addToast({ severity: 'success', summary: 'Salvo', life: 3000 })
// severity: success | info | warn | error
```

## Regra
- Store não chama API diretamente — usa services
- Store não contém lógica de UI (ex: abrir modal)
- Componente não chama API diretamente — usa store ou composable
