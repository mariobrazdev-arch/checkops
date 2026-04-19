# CheckOps — App (Vue 3 + PrimeVue)

Interface PWA mobile-first para execução e gestão de rotinas operacionais.

## Stack

- Vue 3 (Composition API + `<script setup>`)
- PrimeVue 4 (tema Aura dark customizado)
- Pinia (state management)
- Vue Router 4
- Vite + VitePWA
- Axios

## Requisitos

- Node.js >= 20
- npm >= 10

## Instalação

```bash
npm install
cp .env.example .env
```

## Variáveis de ambiente

```env
VITE_API_URL=http://localhost:8000/api/v1
```

## Rodando

```bash
npm run dev    # dev em http://localhost:5173
npm run build  # build de produção
npm run preview
```

## Estrutura

```text
src/
  layouts/      → Admin, Gestor, Colaborador, Auth
  pages/
    auth/        → Login, EsqueciSenha, RedefinirSenha
    admin/       → Dashboard, Empresa, Setores, Usuarios, Rotinas, Auditoria, Relatorios
    gestor/      → Dashboard, Colaboradores, Rotinas, Validacao
    colaborador/ → RotinasHoje, Historico, Perfil
  components/
    camera/      → CameraCaptura.vue (crítico — não alterar sem motivo)
    ui/          → componentes genéricos reutilizáveis
    shared/      → componentes específicos do CheckOps
  stores/        → auth, rotinas, ui (Pinia)
  services/      → api.js + módulos por recurso
  composables/   → useRotinas, useDashboard, useCamera, useAuth
  router/        → index.js + guards por perfil
```

## Perfis e layouts

| Perfil | Layout | Navegação |
| ------ | ------ | --------- |
| `admin` | LayoutAdmin | Sidebar |
| `gestor` | LayoutGestor | Sidebar |
| `colaborador` | LayoutColaborador | Bottom nav (mobile-first) |

## PWA

- `theme_color`: `#C9A84C`
- `background_color`: `#111111`
- Service Worker: assets em cache, API com network-first
