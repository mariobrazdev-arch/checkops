# CheckOps — Frontend (Vue 3 + PrimeVue 4 + PWA)

## Stack
- Vue 3 (Composition API + `<script setup>`) · PrimeVue 4 · Pinia · Vue Router 4
- Vite · Axios · VitePWA · TypeScript opcional (JS por padrão)

## Identidade visual
```css
/* Tokens globais — usar SEMPRE, nunca hardcode de cor */
--color-gold:        #C9A84C;   /* dourado principal */
--color-gold-light:  #E8C97A;   /* hover/destaque */
--color-gold-dark:   #A07830;   /* pressed/active */
--color-bg:          #111111;   /* fundo app */
--color-surface:     #1C1C1C;   /* cards/modais */
--color-surface-2:   #252525;   /* inputs/tables */
--color-border:      #333333;   /* bordas */
--color-text:        #F0F0F0;   /* texto primário */
--color-text-muted:  #888888;   /* texto secundário */

/* Status — badges de rotina */
--status-pendente:      #F59E0B;  /* âmbar */
--status-realizada:     #22C55E;  /* verde */
--status-atrasada:      #EF4444;  /* vermelho */
--status-nao-realizada: #6B7280;  /* cinza */
```

## PrimeVue — Theme customizado
Usar `pt` (PassThrough) para aplicar tokens nos componentes:
```js
// Não criar CSS override — usar pt ou preset Aura dark customizado
```

## Estrutura de arquivos
```
src/
  layouts/
    LayoutAdmin.vue       # sidebar + header para admin
    LayoutGestor.vue      # sidebar + header para gestor
    LayoutColaborador.vue # bottom nav mobile-first
    LayoutAuth.vue        # centralizado, sem nav
  pages/
    auth/                 # Login, EsqueciSenha, RedefinirSenha
    admin/                # Dashboard, Empresa, Setores, Usuarios, Rotinas, Auditoria, Relatorios
    gestor/               # Dashboard, Colaboradores, Rotinas, Validacao
    colaborador/          # RotinasHoje, Historico, Perfil
  components/
    ui/                   # componentes genéricos reutilizáveis
    shared/               # componentes específicos do CheckOps
    camera/               # CameraCaptura.vue (isolado — não alterar sem motivo)
  composables/            # useRotinas, useDashboard, useCamera, useAuth
  stores/                 # auth.store.js, rotinas.store.js, ui.store.js
  services/               # api.js (axios instance) + módulos por recurso
  router/                 # index.js + guards por perfil
  utils/                  # formatters, validators, constants
```

## Convenções

### Componentes
- `<script setup>` sempre
- Props com `defineProps` + defaults
- Emits com `defineEmits` tipados
- Composables para lógica reutilizável — não inline em páginas

### Stores (Pinia)
```js
// stores/auth.store.js
export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  // ...
})
```

### Services (API)
```js
// services/rotinas.service.js
export const rotinasService = {
  listarDoDia: () => api.get('/colaborador/rotinas/hoje'),
  responder: (id, dados) => api.post(`/colaborador/rotinas/${id}/responder`, dados),
}
```

### Router guards
```js
// Perfis protegidos — redireciona se não autorizado
router.beforeEach((to) => {
  if (to.meta.perfil && to.meta.perfil !== authStore.user?.perfil) {
    return '/403'
  }
})
```

## PWA
- Ícones: dourado (#C9A84C) no fundo escuro (#111111)
- `theme_color`: #C9A84C
- `background_color`: #111111
- Service Worker: cache de assets estáticos, API com network-first

## Mobile-first
- Layout colaborador é bottom nav (não sidebar)
- Viewport base: 375px
- Touch targets mínimo: 44px
- Usar `DataView` + `Card` PrimeVue para lista de rotinas (não Table em mobile)
