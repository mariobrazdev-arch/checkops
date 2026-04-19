<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { authService } from '../../services/auth.service.js'

const email = ref('')
const loading = ref(false)
const enviado = ref(false)
const erro = ref('')

async function handleEnviar() {
  erro.value = ''
  loading.value = true
  try {
    await authService.esqueciSenha(email.value)
    enviado.value = true
  } catch (e) {
    erro.value = e?.response?.data?.message ?? 'Erro ao enviar. Tente novamente.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="enviado" class="sucesso">
    <div class="sucesso-icon">✓</div>
    <p class="sucesso-titulo">E-mail enviado!</p>
    <p class="sucesso-texto">
      Verifique sua caixa de entrada. O link de redefinição expira em 60 minutos.
    </p>
    <RouterLink to="/login" class="btn-primary">Voltar ao login</RouterLink>
  </div>

  <form v-else class="form" @submit.prevent="handleEnviar" novalidate>
    <p class="descricao">
      Informe seu e-mail e enviaremos um link para redefinir sua senha.
    </p>

    <div class="field">
      <label for="email">E-mail</label>
      <input
        id="email"
        v-model="email"
        type="email"
        placeholder="seu@email.com"
        autocomplete="email"
        :disabled="loading"
        required
      />
    </div>

    <p v-if="erro" class="erro-msg" role="alert">{{ erro }}</p>

    <button type="submit" class="btn-primary" :disabled="!email || loading">
      <span v-if="loading" class="spinner" aria-hidden="true" />
      {{ loading ? 'Enviando...' : 'Enviar link' }}
    </button>

    <div class="links">
      <RouterLink to="/login" class="link-muted">← Voltar ao login</RouterLink>
    </div>
  </form>
</template>

<style scoped>
.form {
  display: flex;
  flex-direction: column;
  gap: 1.125rem;
}

.descricao {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
  line-height: 1.5;
  margin: 0;
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
}

input:focus {
  border-color: var(--color-gold);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-gold) 20%, transparent);
}

input:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.erro-msg {
  background-color: color-mix(in srgb, var(--status-atrasada) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--status-atrasada) 40%, transparent);
  border-radius: 6px;
  color: var(--status-atrasada);
  font-size: 0.8125rem;
  padding: 0.5rem 0.75rem;
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
  transition: background-color 0.15s;
  min-height: 44px;
  text-decoration: none;
}

.btn-primary:hover:not(:disabled) {
  background-color: var(--color-gold-light);
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
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.links {
  text-align: center;
}

.link-muted {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
  transition: color 0.15s;
}

.link-muted:hover {
  color: var(--color-gold);
}

/* Estado de sucesso */
.sucesso {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  text-align: center;
  padding: 0.5rem 0;
}

.sucesso-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  background-color: color-mix(in srgb, var(--status-realizada) 15%, transparent);
  color: var(--status-realizada);
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sucesso-titulo {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
}

.sucesso-texto {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
  line-height: 1.5;
  margin: 0;
}

.sucesso .btn-primary {
  margin-top: 0.5rem;
  width: 100%;
}
</style>
