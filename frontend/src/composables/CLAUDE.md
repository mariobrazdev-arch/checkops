# Composables

Lógica reutilizável extraída das páginas. Sempre prefixo `use`.

## Composables existentes

### useAuth.js
```js
// Expõe: user, isAdmin, isGestor, isColaborador, login(), logout()
// Usa: authStore internamente
```

### useRotinas.js
```js
// Expõe: rotinasHoje, carregando, buscarHoje(), responderSim(), responderNao()
// Usado em: ColaboradorRotinas.vue
```

### useCamera.js
```js
// Encapsula abertura/fechamento da CameraCaptura
// Expõe: modalAberto, fotoCapturada, abrirCamera(), onCapturada(callback)
// NÃO contém lógica de câmera — delega para CameraCaptura.vue
```

### useDashboard.js
```js
// Expõe: dados, carregando, filtros, buscar(filtros)
// Usado em: GestorDashboard.vue, AdminDashboard.vue
```

### usePagination.js
```js
// Genérico — expõe: page, perPage, total, proxima(), anterior()
```

### useToast.js
```js
// Wrapper do uiStore.addToast()
// Expõe: sucesso(), erro(), info(), aviso()
```

## Regra
- Composable não acessa `$route` ou `$router` — recebe como parâmetro se necessário
- Composable não renderiza nada — só lógica e estado
