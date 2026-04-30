<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.store.js'
import { usePWAInstall } from '../composables/usePWAInstall.js'

const router = useRouter()
const authStore = useAuthStore()
const { podeInstalar, instalar, dispensar } = usePWAInstall()

async function logout() {
  await authStore.logout()
  router.push('/login')
}

// ── Detecção offline ──────────────────────────────────────────────────────
const isOffline = ref(!navigator.onLine)

function onOnline()  { isOffline.value = false }
function onOffline() { isOffline.value = true  }

onMounted(() => {
  window.addEventListener('online',  onOnline)
  window.addEventListener('offline', onOffline)
})

onBeforeUnmount(() => {
  window.removeEventListener('online',  onOnline)
  window.removeEventListener('offline', onOffline)
})
</script>

<template>
  <div class="layout-colaborador">
    <!-- Banner offline -->
    <div v-if="isOffline" class="banner-offline" role="status" aria-live="polite">
      <i class="pi pi-wifi" aria-hidden="true" />
      <span>Você está sem conexão</span>
    </div>

    <!-- Banner de instalação PWA -->
    <div v-if="podeInstalar" class="banner-pwa" role="complementary" aria-label="Instalar aplicativo">
      <div class="banner-pwa__texto">
        <i class="pi pi-mobile" aria-hidden="true" />
        <span>Instale o CheckOps na sua tela inicial</span>
      </div>
      <div class="banner-pwa__acoes">
        <button class="btn-instalar" aria-label="Instalar aplicativo CheckOps" @click="instalar">
          Instalar
        </button>
        <button class="btn-dispensar" aria-label="Fechar banner de instalação" @click="dispensar">
          Agora não
        </button>
      </div>
    </div>

    <header class="header">
      <div class="header-info">
        <span class="user-name">{{ authStore.user?.nome }}</span>
        <span class="user-setor">{{ authStore.user?.setor_id ? 'Setor' : '' }}</span>
      </div>
      <div class="header-logo" aria-label="CheckOps · Check-in de Operações">
        <span class="logo-check" aria-hidden="true">CHECK</span><span class="logo-ops" aria-hidden="true">OPS</span>
      </div>
      <button class="btn-logout" @click="logout" aria-label="Sair">
        <i class="pi pi-sign-out" />
      </button>
    </header>

    <main class="content" id="main-content">
      <slot />
    </main>

    <nav class="bottom-nav" aria-label="Navegação principal">
      <RouterLink to="/colaborador/rotinas" class="nav-tab" active-class="nav-tab--active" aria-label="Rotinas do dia">
        <i class="pi pi-list-check" aria-hidden="true" />
        <span>Rotinas</span>
      </RouterLink>
      <RouterLink to="/colaborador/historico" class="nav-tab" active-class="nav-tab--active" aria-label="Histórico de rotinas">
        <i class="pi pi-clock" aria-hidden="true" />
        <span>Histórico</span>
      </RouterLink>
      <RouterLink to="/colaborador/perfil" class="nav-tab" active-class="nav-tab--active" aria-label="Meu perfil">
        <i class="pi pi-user" aria-hidden="true" />
        <span>Perfil</span>
      </RouterLink>
    </nav>
  </div>
</template>

<style scoped>
/* ── Banners ────────────────────────────────────────────────────────────── */
.banner-offline {
  background: #2a1a1a;
  border-bottom: 1px solid var(--status-atrasada);
  color: var(--status-atrasada);
  font-size: 0.8rem;
  padding: 0.5rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-align: center;
  justify-content: center;
}

.banner-pwa {
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-gold);
  padding: 0.625rem 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.banner-pwa__texto {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--color-text);
}

.banner-pwa__texto .pi {
  color: var(--color-gold);
  font-size: 1.1rem;
}

.banner-pwa__acoes {
  display: flex;
  gap: 0.5rem;
}

.btn-instalar {
  min-height: 44px;
  padding: 0.4rem 1rem;
  background: var(--color-gold);
  color: #111111;
  border: none;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.btn-instalar:hover { background: var(--color-gold-light); }

.btn-dispensar {
  min-height: 44px;
  padding: 0.4rem 0.75rem;
  background: transparent;
  color: var(--color-text-muted);
  border: none;
  font-size: 0.8rem;
  cursor: pointer;
  white-space: nowrap;
}

.btn-dispensar:hover { color: var(--color-text); }

/* ── Layout principal ────────────────────────────────────────────────────── */
.layout-colaborador {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background-color: var(--color-bg);
}

.header {
  height: 56px;
  background-color: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1rem;
  flex-shrink: 0;
}

.header-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
}

.user-setor {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.header-logo {
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.btn-logout {
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: 1px solid var(--color-border);
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 0.4rem 0.5rem;
  border-radius: 6px;
  font-size: 1rem;
  min-height: 44px;
  min-width: 44px;
  transition: color 0.15s, border-color 0.15s;
}

.btn-logout:hover {
  color: var(--color-text);
  border-color: var(--color-text-muted);
}

.logo-check {
  color: var(--color-gold);
}

.logo-ops {
  color: var(--color-text);
}

.content {
  flex: 1;
  padding: 1rem;
  overflow-y: auto;
  padding-bottom: calc(64px + 1rem);
}

.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 64px;
  background-color: var(--color-surface);
  border-top: 1px solid var(--color-border);
  display: flex;
  align-items: stretch;
  z-index: 100;
}

.nav-tab {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  color: var(--color-text-muted);
  font-size: 0.7rem;
  min-height: 44px;
  min-width: 44px;
  transition: color 0.15s;
}

.nav-tab i {
  font-size: 1.25rem;
}

.nav-tab:hover {
  color: var(--color-text);
}

.nav-tab--active {
  color: var(--color-gold);
}
</style>
