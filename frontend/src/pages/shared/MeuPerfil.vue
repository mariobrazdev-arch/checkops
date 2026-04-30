<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth.store.js'
import { useUiStore } from '../../stores/ui.store.js'
import { authService } from '../../services/auth.service.js'
import { usePushNotifications } from '../../composables/usePushNotifications.js'

const authStore = useAuthStore()
const uiStore = useUiStore()
const push = usePushNotifications()

// ─── Foto de perfil ──────────────────────────────────────────────────────────
const fotoUrl = ref(authStore.user?.foto_perfil_url ?? null)
const fotoInput = ref(null)
const enviandoFoto = ref(false)

function abrirSeletorFoto() {
  fotoInput.value?.click()
}

async function comprimirParaBase64(arquivo) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    const url = URL.createObjectURL(arquivo)
    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Falha ao carregar imagem')) }
    img.onload = () => {
      URL.revokeObjectURL(url)
      const MAX = 800
      let w = img.width, h = img.height
      if (w > MAX || h > MAX) {
        const r = Math.min(MAX / w, MAX / h)
        w = Math.round(w * r)
        h = Math.round(h * r)
      }
      const canvas = document.createElement('canvas')
      canvas.width = w; canvas.height = h
      canvas.getContext('2d').drawImage(img, 0, 0, w, h)
      const base64 = canvas.toDataURL('image/jpeg', 0.75)
      if (!base64 || base64 === 'data:,') { reject(new Error('Falha ao converter imagem')); return }
      resolve(base64)
    }
    img.src = url
  })
}

async function onFotoSelecionada(e) {
  const arquivo = e.target.files?.[0]
  if (!arquivo) return
  enviandoFoto.value = true
  try {
    const base64 = await comprimirParaBase64(arquivo)
    const { data } = await authService.uploadFotoPerfil(base64)
    fotoUrl.value = data.data.foto_perfil_url
    authStore.user = { ...authStore.user, foto_perfil_url: data.data.foto_perfil_url }
    uiStore.addToast({ severity: 'success', summary: 'Foto atualizada', life: 2500 })
  } catch (err) {
    uiStore.addToast({ severity: 'error', summary: 'Erro ao enviar foto', detail: err?.response?.data?.message ?? err?.message, life: 4000 })
  } finally {
    enviandoFoto.value = false
    e.target.value = ''
  }
}

async function removerFoto() {
  enviandoFoto.value = true
  try {
    await authService.removerFotoPerfil()
    fotoUrl.value = null
    authStore.user = { ...authStore.user, foto_perfil_url: null }
    uiStore.addToast({ severity: 'info', summary: 'Foto removida', life: 2500 })
  } catch {
    uiStore.addToast({ severity: 'error', summary: 'Erro ao remover foto', life: 3000 })
  } finally {
    enviandoFoto.value = false
  }
}

// ─── Dados pessoais ──────────────────────────────────────────────────────────
const dadosSalvando = ref(false)
const dadosSucesso = ref(false)
const dadosErro = ref('')
const dados = reactive({
  nome:            authStore.user?.nome ?? '',
  email:           authStore.user?.email ?? '',
  telefone:        authStore.user?.telefone ?? '',
  cpf:             authStore.user?.cpf ?? '',
  sexo:            authStore.user?.sexo ?? '',
  data_nascimento: authStore.user?.data_nascimento ?? '',
})

const opcoesSexo = [
  { label: 'Masculino',          value: 'masculino'     },
  { label: 'Feminino',           value: 'feminino'      },
  { label: 'Outro',              value: 'outro'         },
  { label: 'Prefiro não informar', value: 'nao_informado' },
]

function onTelefoneInput(e) {
  const num = e.target.value.replace(/\D/g, '').slice(0, 11)
  if (num.length <= 10) {
    dados.telefone = num
      .replace(/^(\d{2})(\d)/, '($1) $2')
      .replace(/(\d{4})(\d{1,4})$/, '$1-$2')
  } else {
    dados.telefone = num
      .replace(/^(\d{2})(\d)/, '($1) $2')
      .replace(/(\d{5})(\d{1,4})$/, '$1-$2')
  }
}

function onCpfInput(e) {
  const num = e.target.value.replace(/\D/g, '').slice(0, 11)
  dados.cpf = num
    .replace(/^(\d{3})(\d)/, '$1.$2')
    .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
    .replace(/\.(\d{3})(\d)/, '.$1-$2')
}

async function salvarDados() {
  dadosErro.value = ''
  dadosSucesso.value = false
  dadosSalvando.value = true
  try {
    const { data } = await authService.updatePerfil({
      nome:            dados.nome,
      email:           dados.email,
      telefone:        dados.telefone ? dados.telefone.replace(/\D/g, '') : null,
      cpf:             dados.cpf ? dados.cpf.replace(/\D/g, '') : null,
      sexo:            dados.sexo || null,
      data_nascimento: dados.data_nascimento || null,
    })
    authStore.user = { ...authStore.user, ...data.data }
    dadosSucesso.value = true
    uiStore.addToast({ severity: 'success', summary: 'Perfil atualizado', life: 2500 })
    setTimeout(() => { dadosSucesso.value = false }, 3000)
  } catch (e) {
    dadosErro.value = e?.response?.data?.message ?? 'Erro ao salvar dados'
  } finally {
    dadosSalvando.value = false
  }
}

// ─── Troca de senha ──────────────────────────────────────────────────────────
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

    <!-- Foto de perfil -->
    <section class="card">
      <h2 class="card-titulo">Foto de perfil</h2>
      <div class="foto-area">
        <div class="avatar" :class="{ 'avatar--loading': enviandoFoto }">
          <img v-if="fotoUrl" :src="fotoUrl" alt="Foto de perfil" class="avatar-img" />
          <span v-else class="avatar-inicial">{{ authStore.user?.nome?.[0]?.toUpperCase() ?? '?' }}</span>
          <div v-if="enviandoFoto" class="avatar-overlay">
            <span class="spinner-foto" />
          </div>
        </div>
        <div class="foto-acoes">
          <button type="button" class="btn-secondary" @click="abrirSeletorFoto" :disabled="enviandoFoto">
            <i class="pi pi-camera" />
            {{ fotoUrl ? 'Trocar foto' : 'Adicionar foto' }}
          </button>
          <button
            v-if="fotoUrl"
            type="button"
            class="btn-remover"
            @click="removerFoto"
            :disabled="enviandoFoto"
          >
            <i class="pi pi-trash" />
            Remover
          </button>
        </div>
        <input
          ref="fotoInput"
          type="file"
          accept="image/*"
          capture="user"
          class="input-oculto"
          @change="onFotoSelecionada"
        />
      </div>
    </section>

    <!-- Dados pessoais -->
    <section class="card">
      <h2 class="card-titulo">Dados pessoais</h2>
      <form class="form" @submit.prevent="salvarDados" novalidate>
        <div class="form-grid">
          <div class="field field--full">
            <label for="nome">Nome completo</label>
            <input id="nome" v-model="dados.nome" type="text" required />
          </div>
          <div class="field field--full">
            <label for="email">E-mail</label>
            <input id="email" v-model="dados.email" type="email" required />
          </div>
          <div class="field">
            <label for="telefone">Telefone <span class="label-opcional">(opcional)</span></label>
            <input
              id="telefone"
              :value="dados.telefone"
              type="tel"
              placeholder="(00) 00000-0000"
              inputmode="numeric"
              maxlength="15"
              @input="onTelefoneInput"
            />
          </div>
          <div class="field">
            <label for="cpf">CPF <span class="label-opcional">(opcional)</span></label>
            <input
              id="cpf"
              :value="dados.cpf"
              type="text"
              placeholder="000.000.000-00"
              inputmode="numeric"
              maxlength="14"
              @input="onCpfInput"
            />
          </div>
          <div class="field">
            <label for="sexo">Sexo <span class="label-opcional">(opcional)</span></label>
            <select id="sexo" v-model="dados.sexo" class="input-select">
              <option value="">Não informado</option>
              <option v-for="op in opcoesSexo" :key="op.value" :value="op.value">{{ op.label }}</option>
            </select>
          </div>
          <div class="field">
            <label for="data-nasc">Data de nascimento <span class="label-opcional">(opcional)</span></label>
            <input
              id="data-nasc"
              v-model="dados.data_nascimento"
              type="date"
              class="input-date"
            />
          </div>
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

    <!-- Push — apenas colaboradores -->
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

/* Foto */
.foto-area {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background-color: var(--color-surface-2);
  border: 2px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: hidden;
  position: relative;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-inicial {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--color-gold);
}

.avatar-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.spinner-foto {
  display: block;
  width: 1.25rem;
  height: 1.25rem;
  border: 2px solid rgba(255,255,255,.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

.foto-acoes {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.btn-secondary {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: none;
  border: 1px solid var(--color-gold);
  color: var(--color-gold);
  border-radius: 8px;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-secondary:hover:not(:disabled) {
  background-color: color-mix(in srgb, var(--color-gold) 10%, transparent);
}

.btn-secondary:disabled { opacity: 0.55; cursor: not-allowed; }

.btn-remover {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: none;
  border: 1px solid var(--color-border);
  color: var(--status-atrasada);
  border-radius: 8px;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  cursor: pointer;
  transition: border-color 0.15s;
}

.btn-remover:hover:not(:disabled) { border-color: var(--status-atrasada); }
.btn-remover:disabled { opacity: 0.55; cursor: not-allowed; }

.input-oculto { display: none; }

/* Formulário */
.form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.field--full { grid-column: 1 / -1; }

@media (max-width: 480px) {
  .form-grid { grid-template-columns: 1fr; }
  .field--full { grid-column: span 1; }
  .foto-area { flex-direction: column; align-items: flex-start; }
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

input,
.input-select,
.input-date {
  background-color: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  color: var(--color-text);
  font-size: 0.9375rem;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  width: 100%;
  box-sizing: border-box;
}

.input-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.875rem center;
  cursor: pointer;
}

input:focus,
.input-select:focus,
.input-date:focus {
  border-color: var(--color-gold);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-gold) 20%, transparent);
}

.input-date::-webkit-calendar-picker-indicator {
  filter: invert(0.6);
  cursor: pointer;
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
