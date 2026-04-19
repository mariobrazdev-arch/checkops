<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth.store.js'

const router = useRouter()
const authStore = useAuthStore()

function voltar() {
  if (authStore.destinoPorPerfil) {
    router.push(authStore.destinoPorPerfil)
  } else {
    router.back()
  }
}
</script>

<template>
  <div class="forbidden">
    <div class="forbidden-code">403</div>
    <h1 class="forbidden-titulo">Acesso não autorizado</h1>
    <p class="forbidden-texto">
      Você não tem permissão para acessar esta página.<br />
      Verifique se está usando o perfil correto.
    </p>
    <div class="forbidden-acoes">
      <button class="btn-primary" @click="voltar">Ir ao meu painel</button>
      <RouterLink to="/login" class="link-muted">Fazer login com outra conta</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.forbidden {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 2rem 1rem;
  gap: 1rem;
}

.forbidden-code {
  font-size: 5rem;
  font-weight: 800;
  color: var(--color-gold);
  line-height: 1;
  letter-spacing: -0.02em;
}

.forbidden-titulo {
  font-size: 1.375rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
}

.forbidden-texto {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  line-height: 1.6;
  margin: 0;
}

.forbidden-acoes {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.5rem;
  width: 100%;
  max-width: 240px;
}

.btn-primary {
  width: 100%;
  background-color: var(--color-gold);
  color: #111111;
  border: none;
  border-radius: 8px;
  padding: 0.8125rem;
  font-weight: 700;
  font-size: 0.9375rem;
  cursor: pointer;
  transition: background-color 0.15s;
  min-height: 44px;
}

.btn-primary:hover {
  background-color: var(--color-gold-light);
}

.link-muted {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
  transition: color 0.15s;
}

.link-muted:hover {
  color: var(--color-gold);
}
</style>
