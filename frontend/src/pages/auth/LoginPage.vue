<script setup>
import { ref, computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '../../stores/auth.store.js'
import { useUiStore } from '../../stores/ui.store.js'

const router = useRouter()
const authStore = useAuthStore()
const uiStore = useUiStore()

const email = ref('')
const password = ref('')
const erro = ref('')

const podeSalvar = computed(() => email.value && password.value && !authStore.loading)

async function handleLogin() {
  erro.value = ''
  try {
    await authStore.login({ email: email.value, password: password.value }, router)
  } catch (e) {
    const msg = e?.response?.data?.message
    if (e?.response?.status === 401) {
      erro.value = 'E-mail ou senha incorretos.'
    } else if (e?.response?.status === 422) {
      const erros = e?.response?.data?.errors
      erro.value = erros ? Object.values(erros).flat().join(' ') : (msg ?? 'Dados inválidos.')
    } else {
      erro.value = msg ?? 'Erro ao conectar ao servidor.'
    }
  }
}
</script>

<template>
  <form class="login-form" @submit.prevent="handleLogin" novalidate>
    <div class="field">
      <label for="email">E-mail</label>
      <input
        id="email"
        v-model="email"
        type="email"
        placeholder="seu@email.com"
        autocomplete="username"
        :disabled="authStore.loading"
        required
      />
    </div>

    <div class="field">
      <label for="password">Senha</label>
      <input
        id="password"
        v-model="password"
        type="password"
        placeholder="••••••••"
        autocomplete="current-password"
        :disabled="authStore.loading"
        required
      />
    </div>

    <p v-if="erro" class="erro-msg" role="alert">{{ erro }}</p>

    <button type="submit" class="btn-primary" :disabled="!podeSalvar">
      <span v-if="authStore.loading" class="spinner" aria-hidden="true" />
      {{ authStore.loading ? 'Entrando...' : 'Entrar' }}
    </button>

    <div class="links">
      <RouterLink to="/esqueci-senha" class="link-muted">Esqueci minha senha</RouterLink>
    </div>
  </form>
</template>

<style scoped>
.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.125rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-text-muted);
  letter-spacing: 0.02em;
}

input {
  background-color: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 0.6875rem 0.875rem;
  color: var(--color-text);
  font-size: 0.9375rem;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  width: 100%;
}

input:focus {
  border-color: var(--color-gold);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-gold) 20%, transparent);
}

input:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

input::placeholder {
  color: var(--color-text-muted);
  opacity: 0.5;
}

.erro-msg {
  background-color: color-mix(in srgb, var(--status-atrasada) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--status-atrasada) 40%, transparent);
  border-radius: 6px;
  color: var(--status-atrasada);
  font-size: 0.8125rem;
  padding: 0.5rem 0.75rem;
  margin: 0;
}

.btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background-color: var(--color-gold);
  color: #111111;
  border: none;
  border-radius: 8px;
  padding: 0.8125rem;
  font-weight: 700;
  font-size: 0.9375rem;
  cursor: pointer;
  transition: background-color 0.15s, transform 0.1s;
  margin-top: 0.25rem;
  min-height: 44px;
}

.btn-primary:hover:not(:disabled) {
  background-color: var(--color-gold-light);
}

.btn-primary:active:not(:disabled) {
  background-color: var(--color-gold-dark);
  transform: scale(0.99);
}

.btn-primary:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.spinner {
  display: inline-block;
  width: 1rem;
  height: 1rem;
  border: 2px solid #11111155;
  border-top-color: #111111;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  flex-shrink: 0;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.links {
  text-align: center;
  margin-top: 0.25rem;
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

