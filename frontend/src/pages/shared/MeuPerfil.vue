<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth.store.js'
import { useUiStore } from '../../stores/ui.store.js'
import { authService } from '../../services/auth.service.js'
import { usePushNotifications } from '../../composables/usePushNotifications.js'

const authStore = useAuthStore()
const uiStore = useUiStore()
const push = usePushNotifications()

// Dados do perfil
const dadosSalvando = ref(false)
const dadosSucesso = ref(false)
const dadosErro = ref('')
const dados = reactive({
  nome: authStore.user?.nome ?? '',
  email: authStore.user?.email ?? '',
  telefone: authStore.user?.telefone ?? '',
})

async function salvarDados() {
  dadosErro.value = ''
  dadosSucesso.value = false
  dadosSalvando.value = true
  try {
    await authStore.updatePerfil({ nome: dados.nome, email: dados.email, telefone: dados.telefone })
    dadosSucesso.value = true
    uiStore.addToast({ severity: 'success', summary: 'Perfil atualizado', life: 2500 })
    setTimeout(() => { dadosSucesso.value = false }, 3000)
  } catch (e) {
    dadosErro.value = e?.response?.data?.message ?? 'Erro ao salvar dados'
  } finally {
    dadosSalvando.value = false
  }
}

// Troca de senha
const senhaForm = reactive({
  senha_atual: '',
  nova_senha: '',
  nova_senha_confirmation: '',
})
const senhaSalvando = ref(false)
const senhaSucesso = ref(false)
const senhaErros = ref({})

function validarSenha() {
  const erros = {}
  if (!senhaForm.senha_atual) erros.senha_atual = 'Informe a senha atual'
  if (senhaForm.nova_senha.length < 8) erros.nova_senha = 'A nova senha deve ter ao menos 8 caracteres'
  if (senhaForm.nova_senha !== senhaForm.nova_senha_confirmation) {
    erros.nova_senha_confirmation = 'As senhas não conferem'
  }
  senhaErros.value = erros
  return Object.keys(erros).length === 0
}

async function trocarSenha() {
  if (!validarSenha()) return
  senhaSalvando.value = true
  senhaSucesso.value = false
  try {
    await authService.updatePerfil({
      senha_atual: senhaForm.senha_atual,
      nova_senha: senhaForm.nova_senha,
      nova_senha_confirmation: senhaForm.nova_senha_confirmation,
    })
    senhaSucesso.value = true
    senhaForm.senha_atual = ''
    senhaForm.nova_senha = ''
    senhaForm.nova_senha_confirmation = ''
    senhaErros.value = {}
    uiStore.addToast({ severity: 'success', summary: 'Senha alterada', life: 2500 })
    setTimeout(() => { senhaSucesso.value = false }, 3000)
  } catch (e) {
    const erros = e?.response?.data?.errors
    if (erros) {
      senhaErros.value = Object.fromEntries(
        Object.entries(erros).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      )
    } else {
      senhaErros.value = { geral: e?.response?.data?.message ?? 'Erro ao alterar senha' }
    }
  } finally {
    senhaSalvando.value = false
  }
}

const labelPerfil = {
  admin: 'Administrador',
  gestor: 'Gestor',
  colaborador: 'Colaborador',
}

onMounted(() => push.verificarEstado())

async function togglePush() {
  if (push.inscrito.value) {
    await push.cancelar()
    uiStore.addToast({ severity: 'info', summary: 'Notificações desativadas', life: 2500 })
  } else {
    await push.solicitarPermissao()
    if (push.inscrito.value) {
      uiStore.addToast({ severity: 'success', summary: 'Notificações ativadas', life: 2500 })
    }
  }
}
</script>

<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">Meu Perfil</h1>
      <span class="badge-perfil">{{ labelPerfil[authStore.user?.perfil] }}</span>
    </div>

    <!-- Dados pessoais -->
    <section class="card">
      <h2 class="card-titulo">Dados pessoais</h2>
      <form class="form" @submit.prevent="salvarDados" novalidate>
        <div class="field">
          <label for="nome">Nome completo</label>
          <input id="nome" v-model="dados.nome" type="text" required />
        </div>
        <div class="field">
          <label for="email">E-mail</label>
          <input id="email" v-model="dados.email" type="email" required />
        </div>
        <div class="field">
          <label for="telefone">Telefone <span class="label-opcional">(opcional)</span></label>
          <input id="telefone" v-model="dados.telefone" type="tel" placeholder="(00) 00000-0000" />
        </div>

        <p v-if="dadosErro" class="msg-erro" role="alert">{{ dadosErro }}</p>
        <p v-if="dadosSucesso" class="msg-sucesso" role="status">✓ Dados atualizados com sucesso!</p>

        <div class="form-footer">
          <button type="submit" class="btn-primary" :disabled="dadosSalvando">
            <span v-if="dadosSalvando" class="spinner" aria-hidden="true" />
            {{ dadosSalvando ? 'Salvando...' : 'Salvar dados' }}
          </button>
        </div>
      </form>
    </section>

    <!-- Trocar senha -->
    <section class="card">
      <h2 class="card-titulo">Trocar senha</h2>
      <form class="form" @submit.prevent="trocarSenha" novalidate>
        <div class="field">
          <label for="senha-atual">Senha atual</label>
          <input
            id="senha-atual"
            v-model="senhaForm.senha_atual"
            type="password"
            autocomplete="current-password"
          />
          <span v-if="senhaErros.senha_atual" class="field-erro">{{ senhaErros.senha_atual }}</span>
        </div>
        <div class="field">
          <label for="nova-senha">Nova senha</label>
          <input
            id="nova-senha"
            v-model="senhaForm.nova_senha"
            type="password"
            autocomplete="new-password"
          />
          <span v-if="senhaErros.nova_senha" class="field-erro">{{ senhaErros.nova_senha }}</span>
        </div>
        <div class="field">
          <label for="confirmar-senha">Confirmar nova senha</label>
          <input
            id="confirmar-senha"
            v-model="senhaForm.nova_senha_confirmation"
            type="password"
            autocomplete="new-password"
          />
          <span v-if="senhaErros.nova_senha_confirmation" class="field-erro">
            {{ senhaErros.nova_senha_confirmation }}
          </span>
        </div>

        <p v-if="senhaErros.geral" class="msg-erro" role="alert">{{ senhaErros.geral }}</p>
        <p v-if="senhaSucesso" class="msg-sucesso" role="status">✓ Senha alterada com sucesso!</p>

        <div class="form-footer">
          <button type="submit" class="btn-primary" :disabled="senhaSalvando">
            <span v-if="senhaSalvando" class="spinner" aria-hidden="true" />
            {{ senhaSalvando ? 'Alterando...' : 'Alterar senha' }}
          </button>
        </div>
      </form>
    </section>

    <!-- US-20: Notificações push — apenas para colaboradores -->
    <section v-if="authStore.user?.perfil === 'colaborador'" class="card">
      <h2 class="card-titulo">Notificações push</h2>
      <div class="push-row">
        <div class="push-info">
          <p class="push-label">Receber notificações push</p>
          <p class="push-desc">
            <template v-if="push.permissao.value === 'denied'">
              Bloqueado pelo navegador. Ative nas configurações do site.
            </template>
            <template v-else-if="push.inscrito.value">
              Ativo — você receberá alertas 30 min antes das rotinas.
            </template>
            <template v-else>
              Inativo — ative para não perder rotinas pendentes.
            </template>
          </p>
        </div>
        <button
          v-if="push.permissao.value !== 'denied'"
          class="btn-toggle-push"
          :class="{ ativo: push.inscrito.value }"
          @click="togglePush"
          :disabled="push.carregando.value"
        >
          {{ push.inscrito.value ? 'Desativar' : 'Ativar' }}
        </button>
      </div>
    </section>
  </div>
</template>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 560px;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
}

.badge-perfil {
  background-color: color-mix(in srgb, var(--color-gold) 15%, transparent);
  color: var(--color-gold);
  border: 1px solid color-mix(in srgb, var(--color-gold) 30%, transparent);
  border-radius: 999px;
  padding: 0.2rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.card {
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 1.5rem;
}

.card-titulo {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 1.25rem 0;
  padding-bottom: 0.875rem;
  border-bottom: 1px solid var(--color-border);
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-text-muted);
}

.label-opcional {
  font-weight: 400;
  font-size: 0.75rem;
}

input {
  background-color: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  color: var(--color-text);
  font-size: 0.9375rem;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}

input:focus {
  border-color: var(--color-gold);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-gold) 20%, transparent);
}

.field-erro {
  font-size: 0.75rem;
  color: var(--status-atrasada);
}

.msg-erro {
  background-color: color-mix(in srgb, var(--status-atrasada) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--status-atrasada) 35%, transparent);
  border-radius: 6px;
  color: var(--status-atrasada);
  font-size: 0.8125rem;
  padding: 0.5rem 0.75rem;
  margin: 0;
}

.msg-sucesso {
  font-size: 0.8125rem;
  color: var(--status-realizada);
  margin: 0;
}

.form-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.25rem;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background-color: var(--color-gold);
  color: #111111;
  border: none;
  border-radius: 8px;
  padding: 0.625rem 1.375rem;
  font-weight: 700;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.15s;
  min-height: 40px;
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
  width: 0.875rem;
  height: 0.875rem;
  border: 2px solid #11111155;
  border-top-color: #111111;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Push notifications */
.push-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
.push-label { font-size: .9375rem; font-weight: 500; color: var(--color-text); margin: 0 0 .2rem; }
.push-desc  { font-size: .8125rem; color: var(--color-text-muted); margin: 0; }
.btn-toggle-push {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  color: var(--color-text);
  padding: .45rem 1rem;
  font-size: .875rem;
  cursor: pointer;
  white-space: nowrap;
  transition: all .2s;
}
.btn-toggle-push.ativo {
  background: rgba(201,168,76,.15);
  border-color: var(--color-gold);
  color: var(--color-gold);
}
.btn-toggle-push:disabled { opacity: .5; cursor: not-allowed; }
</style>
