<script setup>
import { ref, reactive, onMounted } from 'vue'
import { superAdminUsuariosService, superAdminEmpresasService } from '../../services/super-admin.service.js'
import { useUiStore } from '../../stores/ui.store.js'

const uiStore  = useUiStore()
const usuarios = ref([])
const empresas = ref([])
const loading  = ref(false)
const salvando = ref(false)
const dialogVisivel = ref(false)
const editandoId    = ref(null)

const form = reactive({ nome: '', email: '', password: '', perfil: 'admin', empresa_id: '', cargo: '', matricula: '', status: 'ativo' })

async function carregar() {
  loading.value = true
  try {
    const [u, e] = await Promise.all([superAdminUsuariosService.listar(), superAdminEmpresasService.listar()])
    usuarios.value = u.data.data ?? []
    empresas.value = e.data.data ?? []
  } finally { loading.value = false }
}

function abrirNovo() {
  editandoId.value = null
  Object.assign(form, { nome: '', email: '', password: '', perfil: 'admin', empresa_id: '', cargo: '', matricula: '', status: 'ativo' })
  dialogVisivel.value = true
}

function abrirEditar(u) {
  editandoId.value = u.id
  Object.assign(form, { nome: u.nome, email: u.email, password: '', perfil: u.perfil, empresa_id: u.empresa_id ?? '', cargo: u.cargo ?? '', matricula: u.matricula ?? '', status: u.status })
  dialogVisivel.value = true
}

async function salvar() {
  salvando.value = true
  try {
    const payload = { ...form, empresa_id: form.empresa_id || null }
    if (!payload.password) delete payload.password
    if (editandoId.value) {
      const { data } = await superAdminUsuariosService.atualizar(editandoId.value, payload)
      const idx = usuarios.value.findIndex(u => u.id === editandoId.value)
      if (idx >= 0) usuarios.value[idx] = data.data
    } else {
      const { data } = await superAdminUsuariosService.criar(payload)
      usuarios.value.unshift(data.data)
    }
    uiStore.addToast({ severity: 'success', summary: 'Usuário salvo', life: 2500 })
    dialogVisivel.value = false
  } catch (e) {
    uiStore.addToast({ severity: 'error', summary: e?.response?.data?.message ?? 'Erro ao salvar', life: 4000 })
  } finally { salvando.value = false }
}

const perfilLabel = { super_admin: 'Super Admin', admin: 'Admin', gestor: 'Gestor', colaborador: 'Colaborador' }
const perfilClass = { super_admin: 'perfil--super', admin: 'perfil--admin', gestor: 'perfil--gestor', colaborador: 'perfil--colaborador' }

onMounted(carregar)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Usuários</h1>
        <p class="page-subtitle">Gerencie usuários de todas as empresas</p>
      </div>
      <button class="btn-primary" @click="abrirNovo"><i class="pi pi-plus" /> Novo usuário</button>
    </div>

    <div v-if="loading" class="loading-state">Carregando...</div>
    <template v-else>
      <!-- Tabela — desktop -->
      <div class="table-card hide-mobile">
        <table class="data-table">
          <thead>
            <tr><th>Nome / E-mail</th><th>Empresa</th><th>Perfil</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
            <tr v-for="u in usuarios" :key="u.id">
              <td>
                <div class="cell-primary">{{ u.nome }}</div>
                <div class="cell-secondary">{{ u.email }}</div>
              </td>
              <td>
                <template v-if="u.empresa">
                  <div class="cell-primary">{{ u.empresa.nome }}</div>
                  <div class="cell-secondary">{{ u.empresa.cnpj ?? '' }}</div>
                </template>
                <span v-else class="sem-empresa">Sem empresa</span>
              </td>
              <td><span :class="['badge-perfil', perfilClass[u.perfil]]">{{ perfilLabel[u.perfil] }}</span></td>
              <td><span :class="['badge', u.status === 'ativo' ? 'badge--ativo' : 'badge--inativo']">{{ u.status }}</span></td>
              <td>
                <button class="btn-icon" @click="abrirEditar(u)" title="Editar usuário e perfil">
                  <i class="pi pi-pencil" />
                </button>
              </td>
            </tr>
            <tr v-if="!usuarios.length"><td colspan="5" class="empty-state">Nenhum usuário</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Cards — mobile -->
      <div class="cards-mobile hide-desktop">
        <div v-if="!usuarios.length" class="empty-state">Nenhum usuário</div>
        <div v-for="u in usuarios" :key="u.id" class="user-card">
          <div class="user-card__top">
            <div>
              <div class="cell-primary">{{ u.nome }}</div>
              <div class="cell-secondary">{{ u.email }}</div>
            </div>
            <button class="btn-icon" @click="abrirEditar(u)" title="Editar">
              <i class="pi pi-pencil" />
            </button>
          </div>
          <div class="user-card__meta">
            <span :class="['badge-perfil', perfilClass[u.perfil]]">{{ perfilLabel[u.perfil] }}</span>
            <span :class="['badge', u.status === 'ativo' ? 'badge--ativo' : 'badge--inativo']">{{ u.status }}</span>
          </div>
          <div class="user-card__empresa">
            <i class="pi pi-building" />
            <span v-if="u.empresa">{{ u.empresa.nome }}</span>
            <span v-else class="sem-empresa">Sem empresa</span>
          </div>
        </div>
      </div>
    </template>

    <div v-if="dialogVisivel" class="dialog-overlay" @click.self="dialogVisivel = false">
      <div class="dialog">
        <div class="dialog-header">
          <h2>{{ editandoId ? 'Editar usuário' : 'Novo usuário' }}</h2>
          <button class="btn-close" @click="dialogVisivel = false"><i class="pi pi-times" /></button>
        </div>
        <div class="dialog-body">
          <div class="form-row">
            <div class="form-group"><label>Nome *</label><input v-model="form.nome" class="form-input" /></div>
            <div class="form-group"><label>E-mail *</label><input v-model="form.email" class="form-input" type="email" /></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Senha {{ editandoId ? '(deixe em branco para manter)' : '*' }}</label><input v-model="form.password" class="form-input" type="password" /></div>
            <div class="form-group">
              <label>Papel (perfil) *</label>
              <select v-model="form.perfil" class="form-input">
                <option value="super_admin">Super Admin</option>
                <option value="admin">Admin (empresa)</option>
                <option value="gestor">Gestor (setor)</option>
                <option value="colaborador">Colaborador</option>
              </select>
              <span class="field-hint">Define o que o usuário pode acessar no sistema</span>
            </div>
          </div>
          <div class="form-group">
            <label>Empresa</label>
            <select v-model="form.empresa_id" class="form-input">
              <option value="">Sem empresa (super admin)</option>
              <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.nome }}</option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Cargo</label><input v-model="form.cargo" class="form-input" /></div>
            <div class="form-group"><label>Matrícula</label><input v-model="form.matricula" class="form-input" /></div>
          </div>
        </div>
        <div class="dialog-footer">
          <button class="btn-secondary" @click="dialogVisivel = false">Cancelar</button>
          <button class="btn-primary" @click="salvar" :disabled="salvando">{{ salvando ? 'Salvando...' : 'Salvar' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1.5rem; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; }
.page-title { font-size: 1.5rem; font-weight: 700; color: var(--color-text); margin: 0; }
.page-subtitle { color: var(--color-text-muted); font-size: 0.875rem; margin: 0.25rem 0 0; }
.table-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 8px; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--color-border); }
.data-table td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--color-border); color: var(--color-text); font-size: 0.9rem; }
.data-table tr:last-child td { border-bottom: none; }
.badge { padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
.badge--ativo { background: rgba(34,197,94,0.15); color: #22c55e; }
.badge--inativo { background: rgba(107,114,128,0.15); color: #6b7280; }
.cell-primary { font-weight: 500; }
.cell-secondary { font-size: 0.8rem; color: var(--color-text-muted); margin-top: 0.15rem; }
.sem-empresa { font-size: 0.8rem; color: var(--color-text-muted); font-style: italic; }
.badge-perfil { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
.perfil--super    { background: rgba(201,168,76,0.2);  color: var(--color-gold); }
.perfil--admin    { background: rgba(99,102,241,0.15); color: #818cf8; }
.perfil--gestor   { background: rgba(34,197,94,0.12);  color: #4ade80; }
.perfil--colaborador { background: rgba(148,163,184,0.12); color: #94a3b8; }
.btn-icon { background: none; border: 1px solid var(--color-border); color: var(--color-text-muted); cursor: pointer; padding: 0.35rem 0.5rem; border-radius: 6px; }
.btn-icon:hover { color: var(--color-text); }
.btn-primary { display: flex; align-items: center; gap: 0.4rem; background: var(--color-gold); color: #111; border: none; padding: 0.6rem 1.25rem; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary { background: none; border: 1px solid var(--color-border); color: var(--color-text); padding: 0.6rem 1.25rem; border-radius: 6px; cursor: pointer; }
.loading-state, .empty-state { text-align: center; color: var(--color-text-muted); padding: 2rem; }
.dialog-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 100; }
.dialog { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 10px; width: 520px; }
.dialog-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); }
.dialog-header h2 { margin: 0; font-size: 1.1rem; color: var(--color-text); }
.btn-close { background: none; border: none; color: var(--color-text-muted); cursor: pointer; }
.dialog-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.dialog-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-group label { font-size: 0.8rem; color: var(--color-text-muted); }
.form-input { background: var(--color-surface-2); border: 1px solid var(--color-border); color: var(--color-text); padding: 0.6rem 0.75rem; border-radius: 6px; font-size: 0.9rem; }
.form-input:focus { outline: none; border-color: var(--color-gold); }
.field-hint { font-size: 0.75rem; color: var(--color-text-muted); }

/* ── Responsivo ──────────────────────────────────────────────────────────── */
.hide-mobile  { display: block; }
.hide-desktop { display: none; }

.cards-mobile { display: flex; flex-direction: column; gap: 0.75rem; }
.user-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.user-card__top { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; }
.user-card__meta { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.user-card__empresa { display: flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; color: var(--color-text-muted); }
.user-card__empresa .pi { font-size: 0.8rem; }

@media (max-width: 768px) {
  .hide-mobile  { display: none; }
  .hide-desktop { display: block; }

  .page-header { flex-wrap: wrap; gap: 0.75rem; }

  .dialog { width: calc(100vw - 2rem); max-height: 90vh; overflow-y: auto; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
