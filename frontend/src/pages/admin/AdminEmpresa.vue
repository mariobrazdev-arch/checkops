<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useUiStore } from '../../stores/ui.store.js'
import { empresaService } from '../../services/empresa.service.js'

const uiStore = useUiStore()

const carregando = ref(true)
const salvando = ref(false)
const sucesso = ref(false)
const erros = ref({})

const form = reactive({
  nome: '',
  cnpj: '',
  telefone: '',
  email: '',
  responsavel: '',
  status: 'ativo',
})

// CNPJ: formatação e validação
function formatarCNPJ(valor) {
  const num = valor.replace(/\D/g, '').slice(0, 14)
  return num
    .replace(/^(\d{2})(\d)/, '$1.$2')
    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
    .replace(/\.(\d{3})(\d)/, '.$1/$2')
    .replace(/(\d{4})(\d)/, '$1-$2')
}

function onCNPJInput(e) {
  form.cnpj = formatarCNPJ(e.target.value)
}

function validarCNPJ(cnpj) {
  const num = cnpj.replace(/\D/g, '')
  if (num.length !== 14) return false
  if (/^(\d)\1{13}$/.test(num)) return false
  const calc = (tamanho) => {
    let soma = 0
    let pos = tamanho - 7
    for (let i = tamanho; i >= 1; i--) {
      soma += parseInt(num.charAt(tamanho - i)) * pos--
      if (pos < 2) pos = 9
    }
    return soma % 11 < 2 ? 0 : 11 - (soma % 11)
  }
  return calc(12) === parseInt(num.charAt(12)) && calc(13) === parseInt(num.charAt(13))
}

function validar() {
  const e = {}
  if (!form.nome.trim()) e.nome = 'Nome é obrigatório'
  if (!form.cnpj) {
    e.cnpj = 'CNPJ é obrigatório'
  } else if (!validarCNPJ(form.cnpj)) {
    e.cnpj = 'CNPJ inválido'
  }
  erros.value = e
  return Object.keys(e).length === 0
}

async function carregar() {
  carregando.value = true
  try {
    const { data } = await empresaService.buscar()
    const emp = data.data
    form.nome = emp.nome ?? ''
    form.cnpj = emp.cnpj ?? ''
    form.telefone = emp.telefone ?? ''
    form.email = emp.email ?? ''
    form.responsavel = emp.responsavel ?? ''
    form.status = emp.status ?? 'ativo'
  } catch {
    uiStore.addToast({ severity: 'error', summary: 'Erro ao carregar empresa', life: 3000 })
  } finally {
    carregando.value = false
  }
}

async function salvar() {
  if (!validar()) return
  salvando.value = true
  sucesso.value = false
  try {
    await empresaService.atualizar({
      nome: form.nome,
      cnpj: form.cnpj,
      telefone: form.telefone || null,
      email: form.email || null,
      responsavel: form.responsavel || null,
      status: form.status,
    })
    sucesso.value = true
    uiStore.addToast({ severity: 'success', summary: 'Empresa atualizada', life: 2500 })
    setTimeout(() => { sucesso.value = false }, 3000)
  } catch (e) {
    const apiErros = e?.response?.data?.errors
    if (apiErros) {
      erros.value = Object.fromEntries(
        Object.entries(apiErros).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      )
    } else {
      uiStore.addToast({
        severity: 'error',
        summary: e?.response?.data?.message ?? 'Erro ao salvar',
        life: 3500,
      })
    }
  } finally {
    salvando.value = false
  }
}

onMounted(carregar)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">Empresa</h1>
      <p class="page-subtitle">Dados cadastrais da sua organização</p>
    </div>

    <div v-if="carregando" class="loading">
      <span class="spinner-lg" aria-label="Carregando..." />
    </div>

    <section v-else class="card">
      <form class="form" @submit.prevent="salvar" novalidate>
        <div class="form-grid">
          <div class="field field--full">
            <label for="emp-nome">Nome da empresa <span class="req">*</span></label>
            <input
              id="emp-nome"
              v-model="form.nome"
              type="text"
              :class="{ 'input-erro': erros.nome }"
              required
            />
            <span v-if="erros.nome" class="field-erro">{{ erros.nome }}</span>
          </div>

          <div class="field">
            <label for="emp-cnpj">CNPJ <span class="req">*</span></label>
            <input
              id="emp-cnpj"
              :value="form.cnpj"
              type="text"
              placeholder="00.000.000/0000-00"
              inputmode="numeric"
              maxlength="18"
              :class="{ 'input-erro': erros.cnpj }"
              @input="onCNPJInput"
            />
            <span v-if="erros.cnpj" class="field-erro">{{ erros.cnpj }}</span>
          </div>

          <div class="field">
            <label for="emp-telefone">Telefone</label>
            <input id="emp-telefone" v-model="form.telefone" type="tel" placeholder="(00) 0000-0000" />
          </div>

          <div class="field">
            <label for="emp-email">E-mail</label>
            <input id="emp-email" v-model="form.email" type="email" placeholder="contato@empresa.com" />
          </div>

          <div class="field">
            <label for="emp-responsavel">Responsável</label>
            <input id="emp-responsavel" v-model="form.responsavel" type="text" />
          </div>
        </div>

        <!-- Toggle status -->
        <div class="status-row">
          <span class="status-label">Status</span>
          <button
            type="button"
            class="toggle"
            :class="form.status === 'ativo' ? 'toggle--on' : 'toggle--off'"
            :aria-pressed="form.status === 'ativo'"
            @click="form.status = form.status === 'ativo' ? 'inativo' : 'ativo'"
          >
            <span class="toggle-thumb" />
          </button>
          <span class="status-value" :class="form.status === 'ativo' ? 'status-ativo' : 'status-inativo'">
            {{ form.status === 'ativo' ? 'Ativa' : 'Inativa' }}
          </span>
        </div>

        <p v-if="sucesso" class="msg-sucesso" role="status">✓ Empresa atualizada com sucesso!</p>

        <div class="form-footer">
          <button type="submit" class="btn-primary" :disabled="salvando">
            <span v-if="salvando" class="spinner" aria-hidden="true" />
            {{ salvando ? 'Salvando...' : 'Salvar alterações' }}
          </button>
        </div>
      </form>
    </section>
  </div>
</template>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 680px;
}

.page-header { display: flex; flex-direction: column; gap: 0.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: var(--color-text); margin: 0; }
.page-subtitle { font-size: 0.875rem; color: var(--color-text-muted); margin: 0; }

.loading {
  display: flex;
  justify-content: center;
  padding: 3rem;
}

.spinner-lg {
  display: block;
  width: 2rem;
  height: 2rem;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-gold);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.card {
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 1.5rem;
}

.form { display: flex; flex-direction: column; gap: 1.25rem; }

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

@media (max-width: 560px) {
  .form-grid { grid-template-columns: 1fr; }
}

.field { display: flex; flex-direction: column; gap: 0.3rem; }
.field--full { grid-column: 1 / -1; }

label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-text-muted);
}

.req { color: var(--status-atrasada); }

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

.input-erro {
  border-color: var(--status-atrasada) !important;
}

.field-erro { font-size: 0.75rem; color: var(--status-atrasada); }

/* Toggle */
.status-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background-color: var(--color-surface-2);
  border-radius: 8px;
}

.status-label { font-size: 0.875rem; color: var(--color-text-muted); flex-shrink: 0; }

.toggle {
  position: relative;
  width: 42px;
  height: 24px;
  border-radius: 999px;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s;
  flex-shrink: 0;
  padding: 0;
}

.toggle--on { background-color: var(--status-realizada); }
.toggle--off { background-color: var(--color-border); }

.toggle-thumb {
  position: absolute;
  top: 3px;
  width: 18px;
  height: 18px;
  background: #fff;
  border-radius: 50%;
  transition: left 0.2s;
}

.toggle--on .toggle-thumb { left: 21px; }
.toggle--off .toggle-thumb { left: 3px; }

.status-value { font-size: 0.875rem; font-weight: 600; }
.status-ativo { color: var(--status-realizada); }
.status-inativo { color: var(--color-text-muted); }

.msg-sucesso { font-size: 0.8125rem; color: var(--status-realizada); margin: 0; }

.form-footer { display: flex; justify-content: flex-end; }

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

.btn-primary:hover:not(:disabled) { background-color: var(--color-gold-light); }
.btn-primary:disabled { opacity: 0.55; cursor: not-allowed; }

.spinner {
  display: inline-block;
  width: 0.875rem;
  height: 0.875rem;
  border: 2px solid #11111155;
  border-top-color: #111111;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
