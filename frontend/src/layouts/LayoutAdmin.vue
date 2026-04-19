<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.store.js'

const router = useRouter()
const authStore = useAuthStore()

const navItems = [
  { label: 'Dashboard', icon: 'pi pi-home', to: '/admin/dashboard' },
  { label: 'Empresa', icon: 'pi pi-building', to: '/admin/empresa' },
  { label: 'Setores', icon: 'pi pi-sitemap', to: '/admin/setores' },
  { label: 'Usuários', icon: 'pi pi-users', to: '/admin/usuarios' },
  { label: 'Rotinas', icon: 'pi pi-list-check', to: '/admin/rotinas' },
  { label: 'Auditoria', icon: 'pi pi-shield', to: '/admin/auditoria' },
  { label: 'Relatórios', icon: 'pi pi-chart-bar', to: '/admin/relatorios' },
]

const navMeuSetor = [
  { label: 'Acompanhamento', icon: 'pi pi-chart-bar', to: '/admin/meu-setor/acompanhamento' },
  { label: 'Validação de Fotos', icon: 'pi pi-camera', to: '/admin/meu-setor/validacao' },
]

const temSetor = computed(() => !!authStore.user?.setor_id)

async function logout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="layout-admin">
    <aside class="sidebar">
      <div class="sidebar-logo">
        <div class="logo-mark"><span class="logo-check">CHECK</span><span class="logo-ops">OPS</span></div>
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

        <template v-if="temSetor">
          <div class="nav-divisor">
            <span>Meu Setor</span>
          </div>
          <RouterLink
            v-for="item in navMeuSetor"
            :key="item.to"
            :to="item.to"
            class="nav-item"
            active-class="nav-item--active"
          >
            <i :class="item.icon" />
            <span>{{ item.label }}</span>
          </RouterLink>
        </template>
      </nav>
    </aside>

    <div class="main-wrapper">
      <header class="header">
        <div class="header-user">
          <span class="user-name">{{ authStore.user?.nome }}</span>
          <RouterLink to="/admin/perfil" class="btn-perfil">
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

.sidebar-logo {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border);
}

.logo-mark { font-size: 1.25rem; font-weight: 700; letter-spacing: 0.05em; }
.logo-check { color: var(--color-gold); }
.logo-ops   { color: var(--color-text); }
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
  border-radius: 0;
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

.nav-divisor {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem 0.25rem;
  margin-top: 0.25rem;
  border-top: 1px solid var(--color-border);
}
.nav-divisor span {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--color-gold);
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
</style>
