<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { useUsuarios } from '../../composables/useUsuarios.js'
import { useSetores } from '../../composables/useSetores.js'
import { useAuthStore } from '../../stores/auth.store.js'
import AppConfirmDialog from '../../components/ui/AppConfirmDialog.vue'

const authStore = useAuthStore()
const { usuarios, loading, salvando, meta, buscar, salvar, remover } = useUsuarios()
const { setores, buscar: buscarSetores } = useSetores()

// Filtros
const filtros = reactive({
  setor_id: '',
  perfil: '',
  status: '',
  busca: '',
})

// Aplica filtros com debounce simples
let buscaTimer = null
watch(filtros, () => {
  clearTimeout(buscaTimer)
  buscaTimer = setTimeout(() => aplicarFiltros(), 350)
})

async function aplicarFiltros() {
  const params = {}
  if (filtros.setor_id) params.setor_id = filtros.setor_id
  if (filtros.perfil) params.perfil = filtros.perfil
  if (filtros.status) params.status = filtros.status
  if (filtros.busca.trim()) params.busca = filtros.busca.trim()
  await buscar(params)
}

// Perfis disponíveis (admin vê todos, gestor só colaboradores)
const perfisDisponiveis = computed(() =>
  authStore.isAdmin
    ? [
        { value: 'admin', label: 'Administrador' },
        { value: 'gestor', label: 'Gestor' },
        { value: 'colaborador', label: 'Colaborador' },
      ]
    : [{ value: 'colaborador', label: 'Colaborador' }]
)

const labelPerfil = {
  admin: 'Administrador',
  gestor: 'Gestor',
  colaborador: 'Colaborador',
}

// Dialog de criação/edição
const dialogVisivel = ref(false)
const editandoId = ref(null)
const errosForm = ref({})

const FORM_VAZIO = {
  nome: '',
  email: '',
  matricula: '',
  cargo: '',
  setor_id: '',
  gestor_id: '',
  perfil: 'colaborador',
  status: 'ativo',
  password: '',
  password_confirmation: '',
}

const form = reactive({ ...FORM_VAZIO })

function abrirNovo() {
  editandoId.value = null
  Object.assign(form, { ...FORM_VAZIO })
  errosForm.value = {}
  dialogVisivel.value = true
}

function abrirEditar(usuario) {
  editandoId.value = usuario.id
  form.nome = usuario.nome
  form.email = usuario.email
  form.matricula = usuario.matricula ?? ''
  form.cargo = usuario.cargo ?? ''
  form.setor_id = usuario.setor_id ?? ''
  form.gestor_id = usuario.gestor_id ?? ''
  form.perfil = usuario.perfil
  form.status = usuario.status
  form.password = ''
  form.password_confirmation = ''
  errosForm.value = {}
  dialogVisivel.value = true
}

function validarForm() {
  const e = {}
  if (!form.nome.trim()) e.nome = 'Nome é obrigatório'
  if (!form.email.trim()) e.email = 'E-mail é obrigatório'
  if (!editandoId.value && form.password.length < 8) e.password = 'Senha deve ter ao menos 8 caracteres'
  if (form.password && form.password !== form.password_confirmation) {
    e.password_confirmation = 'As senhas não conferem'
  }
  errosForm.value = e
  return Object.keys(e).length === 0
}

async function submeterForm() {
  if (!validarForm()) return
  const dados = {
    nome: form.nome,
    email: form.email,
    matricula: form.matricula || null,
    cargo: form.cargo || null,
    setor_id: form.setor_id || null,
    gestor_id: form.gestor_id || null,
    perfil: form.perfil,
    status: form.status,
  }
  if (form.password) {
    dados.password = form.password
    dados.password_confirmation = form.password_confirmation
  }
  try {
    await salvar(dados, editandoId.value)
    dialogVisivel.value = false
  } catch (e) {
    const apiErros = e?.response?.data?.errors
    if (apiErros) {
      errosForm.value = Object.fromEntries(
        Object.entries(apiErros).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      )
    }
  }
}

// Confirmar exclusão
const confirmVisivel = ref(false)
const usuarioParaRemover = ref(null)

function pedirRemover(usuario) {
  usuarioParaRemover.value = usuario
  confirmVisivel.value = true
}

async function confirmarRemover() {
  if (!usuarioParaRemover.value) return
  try {
    await remover(usuarioParaRemover.value.id)
  } finally {
    confirmVisivel.value = false
    usuarioParaRemover.value = null
  }
}

// Mobile
const isMobile = ref(window.innerWidth <= 640)
function onResize() { isMobile.value = window.innerWidth <= 640 }

onMounted(() => {
  buscar()
  if (authStore.isAdmin) buscarSetores()
  window.addEventListener('resize', onResize)
})

onUnmounted(() => window.removeEventListener('resize', onResize))

// Setores para filtro/select
const nomesSetor = computed(() => {
  const map = {}
  setores.value.forEach((s) => { map[s.id] = s.nome })
  return map
})
</script>

<template>
  <div class="page">
    <!-- Cabeçalho -->
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ authStore.isGestor ? 'Colaboradores' : 'Usuários' }}</h1>
        <p class="page-subtitle">
          {{ authStore.isGestor ? 'Colaboradores do seu setor' : 'Gerencie todos os usuários da empresa' }}
        </p>
      </div>
      <button class="btn-primary" @click="abrirNovo">
        <i class="pi pi-plus" />
        {{ authStore.isGestor ? 'Novo colaborador' : 'Novo usuário' }}
      </button>
    </div>

    <!-- Filtros -->
    <div class="filtros">
      <div class="search-wrapper">
        <i class="pi pi-search search-icon" />
        <input
          v-model="filtros.busca"
          class="search-input"
          type="search"
          placeholder="Buscar por nome ou e-mail..."
        />
      </div>

      <select v-if="authStore.isAdmin" v-model="filtros.setor_id" class="filtro-select">
        <option value="">Todos os setores</option>
        <option v-for="s in setores" :key="s.id" :value="s.id">{{ s.nome }}</option>
      </select>

      <select v-if="authStore.isAdmin" v-model="filtros.perfil" class="filtro-select">
        <option value="">Todos os perfis</option>
        <option v-for="p in perfisDisponiveis" :key="p.value" :value="p.value">{{ p.label }}</option>
      </select>

      <select v-model="filtros.status" class="filtro-select">
        <option value="">Todos os status</option>
        <option value="ativo">Ativo</option>
        <option value="inativo">Inativo</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading">
      <span class="spinner-lg" aria-label="Carregando..." />
    </div>

    <!-- Vazio -->
    <div v-else-if="!usuarios.length" class="empty-state">
      <i class="pi pi-users empty-icon" />
      <p>Nenhum usuário encontrado</p>
    </div>

    <!-- Tabela desktop -->
    <div v-else-if="!isMobile" class="table-wrapper">
      <table class="tabela">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Matrícula</th>
            <th>Setor</th>
            <th>Perfil</th>
            <th>Status</th>
            <th class="col-acoes">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in usuarios" :key="u.id">
            <td class="td-nome">
              <span>{{ u.nome }}</span>
              <small class="td-email">{{ u.email }}</small>
            </td>
            <td>{{ u.matricula ?? '—' }}</td>
            <td>{{ nomesSetor[u.setor_id] ?? '—' }}</td>
            <td>
              <span class="badge badge-perfil" :class="`badge-perfil--${u.perfil}`">
                {{ labelPerfil[u.perfil] }}
              </span>
            </td>
            <td>
              <span class="badge" :class="u.status === 'ativo' ? 'badge--ativo' : 'badge--inativo'">
                {{ u.status === 'ativo' ? 'Ativo' : 'Inativo' }}
              </span>
            </td>
            <td class="col-acoes">
              <div class="acoes">
                <button class="btn-icon" title="Editar" @click="abrirEditar(u)">
                  <i class="pi pi-pencil" />
                </button>
                <button
                  v-if="authStore.isAdmin"
                  class="btn-icon btn-icon--danger"
                  title="Remover"
                  @click="pedirRemover(u)"
                >
                  <i class="pi pi-trash" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Cards mobile -->
    <div v-else class="cards-list">
      <div v-for="u in usuarios" :key="u.id" class="card-item">
        <div class="card-item-header">
          <div>
            <p class="card-item-nome">{{ u.nome }}</p>
            <p class="card-item-email">{{ u.email }}</p>
          </div>
          <span class="badge" :class="u.status === 'ativo' ? 'badge--ativo' : 'badge--inativo'">
            {{ u.status === 'ativo' ? 'Ativo' : 'Inativo' }}
          </span>
        </div>
        <div class="card-item-tags">
          <span class="badge badge-perfil" :class="`badge-perfil--${u.perfil}`">
            {{ labelPerfil[u.perfil] }}
          </span>
          <span v-if="nomesSetor[u.setor_id]" class="tag-setor">
            <i class="pi pi-sitemap" /> {{ nomesSetor[u.setor_id] }}
          </span>
        </div>
        <div class="card-item-acoes">
          <button class="btn-ghost" @click="abrirEditar(u)">
            <i class="pi pi-pencil" /> Editar
          </button>
          <button v-if="authStore.isAdmin" class="btn-ghost btn-ghost--danger" @click="pedirRemover(u)">
            <i class="pi pi-trash" /> Remover
          </button>
        </div>
      </div>
    </div>

    <!-- Dialog criar/editar -->
    <Teleport to="body">
      <Transition name="overlay">
        <div v-if="dialogVisivel" class="overlay" @click.self="dialogVisivel = false">
          <div class="dialog">
            <div class="dialog-header">
              <h2 class="dialog-titulo">
                {{ editandoId
                  ? (authStore.isGestor ? 'Editar colaborador' : 'Editar usuário')
                  : (authStore.isGestor ? 'Novo colaborador' : 'Novo usuário') }}
              </h2>
              <button class="btn-close" @click="dialogVisivel = false">
                <i class="pi pi-times" />
              </button>
            </div>

            <form class="dialog-form" @submit.prevent="submeterForm" novalidate>
              <div class="form-grid">
                <!-- Nome | E-mail -->
                <div class="field">
                  <label for="u-nome">Nome completo <span class="req">*</span></label>
                  <input id="u-nome" v-model="form.nome" type="text" :class="{ 'input-erro': errosForm.nome }" required />
                  <span v-if="errosForm.nome" class="field-erro">{{ errosForm.nome }}</span>
                </div>
                <div class="field">
                  <label for="u-email">E-mail <span class="req">*</span></label>
                  <input id="u-email" v-model="form.email" type="email" :class="{ 'input-erro': errosForm.email }" required />
                  <span v-if="errosForm.email" class="field-erro">{{ errosForm.email }}</span>
                </div>

                <!-- Matrícula | Cargo -->
                <div class="field">
                  <label for="u-matricula">Matrícula</label>
                  <input id="u-matricula" v-model="form.matricula" type="text" />
                </div>
                <div class="field">
                  <label for="u-cargo">Cargo</label>
                  <input id="u-cargo" v-model="form.cargo" type="text" />
                </div>

                <!-- Perfil | Setor — apenas admin -->
                <template v-if="authStore.isAdmin">
                  <div class="field">
                    <label for="u-perfil">Perfil</label>
                    <select id="u-perfil" v-model="form.perfil">
                      <option v-for="p in perfisDisponiveis" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                  </div>
                  <div class="field">
                    <label for="u-setor">Setor</label>
                    <select id="u-setor" v-model="form.setor_id">
                      <option value="">— Sem setor —</option>
                      <option v-for="s in setores" :key="s.id" :value="s.id">{{ s.nome }}</option>
                    </select>
                  </div>
                </template>

                <!-- Senha | Confirmar senha -->
                <div class="field">
                  <label for="u-senha">{{ editandoId ? 'Nova senha (em branco = manter)' : 'Senha *' }}</label>
                  <input id="u-senha" v-model="form.password" type="password" autocomplete="new-password"
                    :required="!editandoId" :class="{ 'input-erro': errosForm.password }" />
                  <span v-if="errosForm.password" class="field-erro">{{ errosForm.password }}</span>
                </div>
                <div class="field">
                  <label for="u-senha-conf">Confirmar senha</label>
                  <input id="u-senha-conf" v-model="form.password_confirmation" type="password"
                    autocomplete="new-password" :class="{ 'input-erro': errosForm.password_confirmation }" />
                  <span v-if="errosForm.password_confirmation" class="field-erro">{{ errosForm.password_confirmation }}</span>
                </div>
              </div>

              <!-- Toggle status -->
              <div class="status-row">
                <span class="status-label">Status</span>
                <button
                  type="button"
                  class="toggle"
                  :class="form.status === 'ativo' ? 'toggle--on' : 'toggle--off'"
                  @click="form.status = form.status === 'ativo' ? 'inativo' : 'ativo'"
                >
                  <span class="toggle-thumb" />
                </button>
                <span :class="form.status === 'ativo' ? 'status-ativo' : 'status-inativo'">
                  {{ form.status === 'ativo' ? 'Ativo' : 'Inativo' }}
                </span>
              </div>

              <div class="dialog-footer">
                <button type="button" class="btn-cancelar" @click="dialogVisivel = false">
                  Cancelar
                </button>
                <button type="submit" class="btn-primary" :disabled="salvando">
                  <span v-if="salvando" class="spinner" />
                  {{ salvando ? 'Salvando...' : 'Salvar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Confirmar exclusão -->
    <AppConfirmDialog
      v-model:visivel="confirmVisivel"
      titulo="Remover usuário"
      label-confirmar="Remover"
      @confirmar="confirmarRemover"
    >
      <p>
        Deseja remover
        <strong style="color: var(--color-text)">{{ usuarioParaRemover?.nome }}</strong>?
        O usuário perderá acesso imediatamente.
      </p>
    </AppConfirmDialog>
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1.25rem; }

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.page-title { font-size: 1.5rem; font-weight: 700; color: var(--color-text); margin: 0; }
.page-subtitle { font-size: 0.875rem; color: var(--color-text-muted); margin: 0; }

.filtros {
  display: flex;
  flex-wrap: wrap;
  gap: 0.625rem;
}

.search-wrapper {
  position: relative;
  flex: 1;
  min-width: 200px;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted);
  font-size: 0.875rem;
  pointer-events: none;
}

.search-input {
  width: 100%;
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 0.5625rem 0.875rem 0.5625rem 2.125rem;
  color: var(--color-text);
  font-size: 0.875rem;
  outline: none;
  transition: border-color 0.15s;
}

.search-input:focus { border-color: var(--color-gold); }

.filtro-select {
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 0.5625rem 2rem 0.5625rem 0.75rem;
  color: var(--color-text);
  font-size: 0.875rem;
  outline: none;
  cursor: pointer;
  transition: border-color 0.15s;
  appearance: auto;
}

.filtro-select:focus { border-color: var(--color-gold); }
.filtro-select option { background-color: var(--color-surface-2); }

.loading { display: flex; justify-content: center; padding: 3rem; }

.spinner-lg {
  display: block;
  width: 2rem;
  height: 2rem;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-gold);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.empty-state { text-align: center; padding: 3rem; color: var(--color-text-muted); }
.empty-icon { font-size: 2.5rem; display: block; margin-bottom: 0.75rem; opacity: 0.4; }

/* Tabela */
.table-wrapper {
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  overflow: hidden;
}

.tabela { width: 100%; border-collapse: collapse; }

.tabela th {
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0.875rem 1rem;
  border-bottom: 1px solid var(--color-border);
  background-color: var(--color-surface-2);
}

.tabela td {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  color: var(--color-text);
  border-bottom: 1px solid color-mix(in srgb, var(--color-border) 50%, transparent);
  vertical-align: middle;
}

.tabela tr:last-child td { border-bottom: none; }
.tabela tr:hover td { background-color: color-mix(in srgb, var(--color-surface-2) 50%, transparent); }

.td-nome { display: flex; flex-direction: column; }
.td-email { font-size: 0.75rem; color: var(--color-text-muted); margin-top: 0.15rem; }
.col-acoes { width: 100px; }

.badge {
  display: inline-block;
  padding: 0.2rem 0.625rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge--ativo {
  background-color: color-mix(in srgb, var(--status-realizada) 15%, transparent);
  color: var(--status-realizada);
}

.badge--inativo {
  background-color: color-mix(in srgb, var(--status-nao-realizada) 15%, transparent);
  color: var(--status-nao-realizada);
}

.badge-perfil--admin {
  background-color: color-mix(in srgb, var(--color-gold) 15%, transparent);
  color: var(--color-gold);
}

.badge-perfil--gestor {
  background-color: color-mix(in srgb, #60a5fa 15%, transparent);
  color: #60a5fa;
}

.badge-perfil--colaborador {
  background-color: color-mix(in srgb, var(--color-text-muted) 15%, transparent);
  color: var(--color-text-muted);
}

.acoes { display: flex; gap: 0.5rem; }

.btn-icon {
  width: 32px; height: 32px;
  display: flex; align-items: center; justify-content: center;
  background: none;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  color: var(--color-text-muted);
  cursor: pointer;
  transition: color 0.15s, border-color 0.15s;
}

.btn-icon:hover { color: var(--color-text); border-color: var(--color-text-muted); }
.btn-icon--danger:hover { color: var(--status-atrasada); border-color: var(--status-atrasada); }

/* Cards mobile */
.cards-list { display: flex; flex-direction: column; gap: 0.75rem; }

.card-item {
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.card-item-header { display: flex; align-items: flex-start; justify-content: space-between; }
.card-item-nome { font-weight: 600; color: var(--color-text); margin: 0; }
.card-item-email { font-size: 0.75rem; color: var(--color-text-muted); margin: 0.125rem 0 0; }

.card-item-tags { display: flex; flex-wrap: wrap; gap: 0.4rem; }

.tag-setor {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.card-item-acoes { display: flex; gap: 0.5rem; margin-top: 0.25rem; }

.btn-ghost {
  display: flex; align-items: center; gap: 0.35rem;
  background: none;
  border: 1px solid var(--color-border);
  color: var(--color-text-muted);
  border-radius: 6px;
  padding: 0.4rem 0.75rem;
  font-size: 0.8125rem;
  cursor: pointer;
  transition: color 0.15s;
  min-height: 36px;
}

.btn-ghost:hover { color: var(--color-text); }
.btn-ghost--danger:hover { color: var(--status-atrasada); border-color: var(--status-atrasada); }

/* Botão primário */
.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background-color: var(--color-gold);
  color: #111111;
  border: none;
  border-radius: 8px;
  padding: 0.625rem 1.125rem;
  font-weight: 700;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.15s;
  min-height: 40px;
  white-space: nowrap;
}

.btn-primary:hover:not(:disabled) { background-color: var(--color-gold-light); }
.btn-primary:disabled { opacity: 0.55; cursor: not-allowed; }

/* Dialog */
.overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.65);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 900;
  padding: 1rem;
  overflow-y: auto;
}

.dialog {
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 1.5rem;
  width: 100%;
  max-width: 560px;
  margin: auto;
}

.dialog-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}

.dialog-titulo { font-size: 1.0625rem; font-weight: 600; color: var(--color-text); margin: 0; }

.btn-close {
  background: none; border: none;
  color: var(--color-text-muted);
  cursor: pointer; font-size: 1rem; padding: 0.25rem;
  transition: color 0.15s;
}

.btn-close:hover { color: var(--color-text); }

.dialog-form { display: flex; flex-direction: column; gap: 1rem; }

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

@media (max-width: 480px) {
  .form-grid { grid-template-columns: 1fr; }
}

.field { display: flex; flex-direction: column; gap: 0.3rem; }
.field--full { grid-column: 1 / -1; }

label { font-size: 0.8125rem; font-weight: 500; color: var(--color-text-muted); }
.req { color: var(--status-atrasada); }

input, select {
  background-color: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  color: var(--color-text);
  font-size: 0.875rem;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  font-family: inherit;
}

input:focus, select:focus {
  border-color: var(--color-gold);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-gold) 20%, transparent);
}

select option { background-color: var(--color-surface-2); }
.input-erro { border-color: var(--status-atrasada) !important; }
.field-erro { font-size: 0.75rem; color: var(--status-atrasada); }

.status-row { display: flex; align-items: center; gap: 0.75rem; }
.status-label { font-size: 0.875rem; color: var(--color-text-muted); }

.toggle {
  position: relative;
  width: 42px; height: 24px;
  border-radius: 999px;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s;
  padding: 0;
}

.toggle--on { background-color: var(--status-realizada); }
.toggle--off { background-color: var(--color-border); }

.toggle-thumb {
  position: absolute;
  top: 3px;
  width: 18px; height: 18px;
  background: #fff;
  border-radius: 50%;
  transition: left 0.2s;
}

.toggle--on .toggle-thumb { left: 21px; }
.toggle--off .toggle-thumb { left: 3px; }

.status-ativo { font-size: 0.875rem; color: var(--status-realizada); font-weight: 600; }
.status-inativo { font-size: 0.875rem; color: var(--color-text-muted); font-weight: 600; }

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.btn-cancelar {
  background: none;
  border: 1px solid var(--color-border);
  color: var(--color-text-muted);
  border-radius: 8px;
  padding: 0.625rem 1.125rem;
  cursor: pointer;
  font-size: 0.875rem;
  transition: border-color 0.15s, color 0.15s;
}

.btn-cancelar:hover { border-color: var(--color-text-muted); color: var(--color-text); }

.spinner {
  display: inline-block;
  width: 0.875rem; height: 0.875rem;
  border: 2px solid #11111155;
  border-top-color: #111111;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

.overlay-enter-active, .overlay-leave-active { transition: opacity 0.2s; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }

@keyframes spin { to { transform: rotate(360deg); } }
</style>
