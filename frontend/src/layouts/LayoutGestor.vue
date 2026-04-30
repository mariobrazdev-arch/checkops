<script setup>
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth.store.js'

const router = useRouter()
const route  = useRoute()
const authStore = useAuthStore()

const sidebarAberta = ref(false)
watch(() => route.path, () => { sidebarAberta.value = false })

const navItems = [
  { label: 'Dashboard', icon: 'pi pi-home', to: '/gestor/dashboard' },
  { label: 'Colaboradores', icon: 'pi pi-users', to: '/gestor/colaboradores' },
  { label: 'Rotinas', icon: 'pi pi-list-check', to: '/gestor/rotinas' },
  { label: 'Acompanhamento', icon: 'pi pi-chart-bar', to: '/gestor/acompanhamento' },
  { label: 'Validação de Fotos', icon: 'pi pi-camera', to: '/gestor/validacao' },
]

async function logout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="layout-gestor">
    <Teleport to="body">
      <div v-if="sidebarAberta" class="sidebar-overlay" @click="sidebarAberta = false" />
    </Teleport>

    <aside class="sidebar" :class="{ 'sidebar--aberta': sidebarAberta }">
      <div class="sidebar-logo">
        <div class="logo-mark"><span class="logo-check">CHECK</span><span class="logo-ops">OPS</span></div>
        <p class="logo-tagline">Check-in de Operações</p>
        <button class="btn-fechar-sidebar" @click="sidebarAberta = false" aria-label="Fechar menu">
          <i class="pi pi-times" />
        </button>
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
        <button class="btn-hamburger" @click="sidebarAberta = !sidebarAberta" aria-label="Menu">
          <i class="pi pi-bars" />
        </button>
        <div class="header-user">
          <span class="user-name">{{ authStore.user?.nome }}</span>
          <RouterLink to="/gestor/perfil" class="btn-perfil">
            <i class="pi pi-user" />
            Meu perfil
          </RouterLink>
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
.layout-gestor {
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
.logo-mark { font-size: 1.25rem; font-weight: 700; letter-spacing: 0.05em; }

.logo-check {
  color: var(--color-gold);
}

.logo-ops { color: var(--color-text); }
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

.nav-item:hover {
  background-color: var(--color-surface-2);
  color: var(--color-text);
}

.nav-item--active {
  color: var(--color-gold);
  background-color: var(--color-surface-2);
  border-left: 3px solid var(--color-gold);
}

.main-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.header {
  height: 56px;
  background-color: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 1.5rem;
}

.header-user {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-name {
  color: var(--color-text-muted);
  font-size: 0.875rem;
}

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

.btn-logout:hover {
  color: var(--color-text);
  border-color: var(--color-text-muted);
}

.btn-perfil {
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

.btn-perfil:hover {
  color: var(--color-text);
  border-color: var(--color-text-muted);
}

.content {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
}

.btn-hamburger {
  display: none;
  background: none;
  border: none;
  color: var(--color-text-muted);
  font-size: 1.1rem;
  cursor: pointer;
  padding: 0.4rem;
  margin-right: auto;
}

.btn-fechar-sidebar {
  display: none;
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  font-size: 1rem;
  padding: 0.25rem;
  position: absolute;
  top: 1rem;
  right: 1rem;
}

@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    top: 0;
    left: -240px;
    height: 100vh;
    z-index: 300;
    transition: left 0.25s ease;
  }

  .sidebar--aberta {
    left: 0;
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.5);
  }

  .sidebar-logo {
    position: relative;
  }

  .btn-fechar-sidebar {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .btn-hamburger {
    display: flex;
    align-items: center;
  }

  .user-name {
    display: none;
  }
}
</style>

<style>
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  z-index: 299;
}
</style>
