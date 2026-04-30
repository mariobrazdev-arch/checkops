<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useSetores } from '../../composables/useSetores.js'
import { usuariosService } from '../../services/usuarios.service.js'
import AppConfirmDialog from '../../components/ui/AppConfirmDialog.vue'

const { setores, loading, salvando, removendo, buscar, salvar, remover } = useSetores()

// Busca por nome (filtro local)
const filtroNome = ref('')
const setoresFiltrados = computed(() => {
  if (!filtroNome.value.trim()) return setores.value
  const q = filtroNome.value.toLowerCase()
  return setores.value.filter((s) => s.nome.toLowerCase().includes(q))
})

// Gestores disponíveis para selecionar
const gestores = ref([])
async function carregarGestores() {
  try {
    const { data } = await usuariosService.listar({ perfil: 'gestor', status: 'ativo' })
    gestores.value = data.data ?? []
  } catch {
    gestores.value = []
  }
}

// Dialog de criação/edição
const dialogVisivel = ref(false)
const editandoId = ref(null)
const errosForm = ref({})

const form = reactive({
  nome: '',
  descricao: '',
  gestor_id: '',
  status: 'ativo',
})

function abrirNovo() {
  editandoId.value = null
  form.nome = ''
  form.descricao = ''
  form.gestor_id = ''
  form.status = 'ativo'
  errosForm.value = {}
  dialogVisivel.value = true
}

function abrirEditar(setor) {
  editandoId.value = setor.id
  form.nome = setor.nome
  form.descricao = setor.descricao ?? ''
  form.gestor_id = setor.gestor_id ?? setor.gestor?.id ?? ''
  form.status = setor.status
  errosForm.value = {}
  dialogVisivel.value = true
}

function validarForm() {
  const e = {}
  if (!form.nome.trim()) e.nome = 'Nome é obrigatório'
  errosForm.value = e
  return Object.keys(e).length === 0
}

async function submeterForm() {
  if (!validarForm()) return
  try {
    await salvar(
      {
        nome: form.nome,
        descricao: form.descricao || null,
        gestor_id: form.gestor_id || null,
        status: form.status,
      },
      editandoId.value
    )
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

// Confirmação de exclusão
const confirmVisivel = ref(false)
const setorParaRemover = ref(null)

function pedirRemover(setor) {
  setorParaRemover.value = setor
  confirmVisivel.value = true
}

async function confirmarRemover() {
  if (!setorParaRemover.value) return
  try {
    await remover(setorParaRemover.value.id)
    confirmVisivel.value = false
    setorParaRemover.value = null
  } catch {
    confirmVisivel.value = false
  }
}

// Mobile
const isMobile = ref(window.innerWidth <= 640)
function onResize() { isMobile.value = window.innerWidth <= 640 }

onMounted(() => {
  buscar()
  carregarGestores()
  window.addEventListener('resize', onResize)
})

import { onUnmounted } from 'vue'
onUnmounted(() => window.removeEventListener('resize', onResize))

</script>

<template>
  <div class="page">
    <!-- Cabeçalho -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Setores</h1>
        <p class="page-subtitle">Gerencie os setores da empresa</p>
      </div>
      <button class="btn-primary" @click="abrirNovo">
        <i class="pi pi-plus" />
        Novo setor
      </button>
    </div>

    <!-- Busca -->
    <div class="toolbar">
      <div class="search-wrapper">
        <i class="pi pi-search search-icon" />
        <input
          v-model="filtroNome"
          class="search-input"
          type="search"
          placeholder="Buscar por nome..."
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading">
      <span class="spinner-lg" aria-label="Carregando..." />
    </div>

    <!-- Vazio -->
    <div v-else-if="!setoresFiltrados.length" class="empty-state">
      <i class="pi pi-sitemap empty-icon" />
      <p>Nenhum setor encontrado</p>
    </div>

    <!-- Tabela (desktop) -->
    <div v-else-if="!isMobile" class="table-wrapper">
      <table class="tabela">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Gestor</th>
            <th>Status</th>
            <th class="col-acoes">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="setor in setoresFiltrados" :key="setor.id">
            <td class="td-nome">{{ setor.nome }}</td>
            <td>{{ setor.gestor?.nome ?? '—' }}</td>
            <td>
              <span class="badge" :class="setor.status === 'ativo' ? 'badge--ativo' : 'badge--inativo'">
                {{ setor.status === 'ativo' ? 'Ativo' : 'Inativo' }}
              </span>
            </td>
            <td class="col-acoes">
              <div class="acoes">
                <button class="btn-icon" title="Editar" @click="abrirEditar(setor)">
                  <i class="pi pi-pencil" />
                </button>
                <button class="btn-icon btn-icon--danger" title="Remover" @click="pedirRemover(setor)">
                  <i class="pi pi-trash" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Cards (mobile) -->
    <div v-else class="cards-list">
      <div v-for="setor in setoresFiltrados" :key="setor.id" class="card-item">
        <div class="card-item-header">
          <span class="card-item-nome">{{ setor.nome }}</span>
          <span class="badge" :class="setor.status === 'ativo' ? 'badge--ativo' : 'badge--inativo'">
            {{ setor.status === 'ativo' ? 'Ativo' : 'Inativo' }}
          </span>
        </div>
        <p v-if="setor.descricao" class="card-item-desc">{{ setor.descricao }}</p>
        <p class="card-item-gestor">
          <i class="pi pi-user" /> {{ setor.gestor?.nome ?? '—' }}
        </p>
        <div class="card-item-acoes">
          <button class="btn-ghost" @click="abrirEditar(setor)">
            <i class="pi pi-pencil" /> Editar
          </button>
          <button class="btn-ghost btn-ghost--danger" @click="pedirRemover(setor)">
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
                {{ editandoId ? 'Editar setor' : 'Novo setor' }}
              </h2>
              <button class="btn-close" @click="dialogVisivel = false">
                <i class="pi pi-times" />
              </button>
            </div>
            <form class="dialog-form" @submit.prevent="submeterForm" novalidate>
              <div class="field">
                <label for="s-nome">Nome <span class="req">*</span></label>
                <input
                  id="s-nome"
                  v-model="form.nome"
                  type="text"
                  :class="{ 'input-erro': errosForm.nome }"
                  required
                />
                <span v-if="errosForm.nome" class="field-erro">{{ errosForm.nome }}</span>
              </div>

              <div class="field">
                <label for="s-desc">Descrição</label>
                <textarea id="s-desc" v-model="form.descricao" rows="2" />
              </div>

              <div class="field">
                <label for="s-gestor">Gestor responsável</label>
                <select id="s-gestor" v-model="form.gestor_id">
                  <option value="">— Sem gestor —</option>
                  <option v-for="g in gestores" :key="g.id" :value="g.id">{{ g.nome }}</option>
                </select>
              </div>

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
      titulo="Remover setor"
      label-confirmar="Remover"
      :carregando="removendo"
      @confirmar="confirmarRemover"
    >
      <p>
        Deseja remover o setor
        <strong style="color: var(--color-text)">{{ setorParaRemover?.nome }}</strong>?
        Esta ação não pode ser desfeita.
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

.toolbar { display: flex; gap: 0.75rem; }

.search-wrapper {
  position: relative;
  flex: 1;
  max-width: 320px;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted);
  font-size: 0.875rem;
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

.empty-state {
  text-align: center;
  padding: 3rem;
  color: var(--color-text-muted);
}

.empty-icon {
  font-size: 2.5rem;
  display: block;
  margin-bottom: 0.75rem;
  opacity: 0.4;
}

/* Tabela */
.table-wrapper {
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  overflow: hidden;
}

.tabela {
  width: 100%;
  border-collapse: collapse;
}

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
  padding: 0.875rem 1rem;
  font-size: 0.875rem;
  color: var(--color-text);
  border-bottom: 1px solid color-mix(in srgb, var(--color-border) 50%, transparent);
}

.tabela tr:last-child td { border-bottom: none; }
.tabela tr:hover td { background-color: color-mix(in srgb, var(--color-surface-2) 50%, transparent); }

.td-nome { font-weight: 500; }
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

.acoes { display: flex; gap: 0.5rem; }

.btn-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
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

.card-item-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-item-nome { font-weight: 600; color: var(--color-text); }
.card-item-desc { font-size: 0.8125rem; color: var(--color-text-muted); margin: 0; }
.card-item-gestor { font-size: 0.8125rem; color: var(--color-text-muted); margin: 0; }

.card-item-acoes {
  display: flex;
  gap: 0.625rem;
  margin-top: 0.25rem;
}

.btn-ghost {
  display: flex;
  align-items: center;
  gap: 0.35rem;
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
}

.dialog {
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 1.5rem;
  width: 100%;
  max-width: 480px;
}

.dialog-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}

.dialog-titulo {
  font-size: 1.0625rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  font-size: 1rem;
  padding: 0.25rem;
  transition: color 0.15s;
}

.btn-close:hover { color: var(--color-text); }

.dialog-form { display: flex; flex-direction: column; gap: 1rem; }

.field { display: flex; flex-direction: column; gap: 0.3rem; }

label { font-size: 0.8125rem; font-weight: 500; color: var(--color-text-muted); }
.req { color: var(--status-atrasada); }

input, textarea, select {
  background-color: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  color: var(--color-text);
  font-size: 0.9375rem;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  font-family: inherit;
}

input:focus, textarea:focus, select:focus {
  border-color: var(--color-gold);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-gold) 20%, transparent);
}

select option { background-color: var(--color-surface-2); }
textarea { resize: vertical; }

.input-erro { border-color: var(--status-atrasada) !important; }
.field-erro { font-size: 0.75rem; color: var(--status-atrasada); }

.status-row { display: flex; align-items: center; gap: 0.75rem; }
.status-label { font-size: 0.875rem; color: var(--color-text-muted); }

.toggle {
  position: relative;
  width: 42px;
  height: 24px;
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
  width: 18px;
  height: 18px;
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
  width: 0.875rem;
  height: 0.875rem;
  border: 2px solid #11111155;
  border-top-color: #111111;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

/* Transitions */
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.2s; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }

@keyframes spin { to { transform: rotate(360deg); } }
</style>
