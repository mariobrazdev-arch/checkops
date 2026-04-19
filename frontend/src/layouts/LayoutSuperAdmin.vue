<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.store.js'

const router = useRouter()
const authStore = useAuthStore()

const navItems = [
  { label: 'Empresas', icon: 'pi pi-building', to: '/super-admin/empresas' },
  { label: 'Usuários', icon: 'pi pi-users', to: '/super-admin/usuarios' },
  { label: 'Planos', icon: 'pi pi-star', to: '/super-admin/planos' },
]

async function logout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="layout-admin">
    <aside class="sidebar">
      <div class="sidebar-logo">
        <div class="logo-mark">
          <span class="logo-check">CHECK</span><span class="logo-ops">OPS</span>
          <span class="badge-super">SUPER</span>
        </div>
        <p class="logo-tagline">Check-in de Operações</p>
      </div>
      <nav class="sidebar-nav">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="nav-item"
          active-class="nav-item--active"
        >
          <i :class="item.icon" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>
    </aside>

    <div class="main-wrapper">
      <header class="header">
        <div class="header-user">
          <span class="user-name">{{ authStore.user?.nome }}</span>
          <button class="btn-logout" @click="logout">
            <i class="pi pi-sign-out" />
            Sair
          </button>
        </div>
      </header>
      <main class="content">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.layout-admin {
  display: flex;
  min-height: 100vh;
  background-color: var(--color-bg);
}
.sidebar {
  width: 240px;
  min-height: 100vh;
  background-color: var(--color-surface);
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}
.sidebar-logo { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); }
.logo-mark { font-size: 1.25rem; font-weight: 700; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem; }
.logo-check { color: var(--color-gold); }
.logo-ops   { color: var(--color-text); }
.badge-super {
  font-size: 0.6rem;
  background: var(--color-gold);
  color: #111;
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
  font-weight: 700;
  letter-spacing: 0.08em;
}
.logo-tagline { margin: 0.2rem 0 0; font-size: 0.68rem; color: var(--color-text-muted); letter-spacing: 0.03em; text-transform: uppercase; }
.sidebar-nav {
  display: flex;
  flex-direction: column;
  padding: 1rem 0;
  gap: 0.25rem;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  color: var(--color-text-muted);
  font-size: 0.9rem;
  transition: background-color 0.15s, color 0.15s;
}
.nav-item:hover { background-color: var(--color-surface-2); color: var(--color-text); }
.nav-item--active { color: var(--color-gold); background-color: var(--color-surface-2); border-left: 3px solid var(--color-gold); }
.main-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.header {
  height: 56px;
  background-color: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 1.5rem;
}
.header-user { display: flex; align-items: center; gap: 1rem; }
.user-name { color: var(--color-text-muted); font-size: 0.875rem; }
.btn-logout {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: none;
  border: 1px solid var(--color-border);
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
  transition: color 0.15s, border-color 0.15s;
}
.btn-logout:hover { color: var(--color-text); border-color: var(--color-text-muted); }
.content { flex: 1; padding: 1.5rem; overflow-y: auto; }
</style>
