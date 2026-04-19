<script setup>
import { ref, reactive, onMounted } from 'vue'
import { superAdminEmpresasService, superAdminPlanosService } from '../../services/super-admin.service.js'
import { useUiStore } from '../../stores/ui.store.js'

const uiStore = useUiStore()
const empresas = ref([])
const planos   = ref([])
const loading  = ref(false)
const salvando = ref(false)
const dialogVisivel = ref(false)
const editandoId    = ref(null)

const form = reactive({
  nome: '', cnpj: '', telefone: '', email: '', responsavel: '', plano_id: '', status: 'ativo',
  admin_nome: '', admin_email: '', admin_password: '',
})

async function carregar() {
  loading.value = true
  try {
    const [e, p] = await Promise.all([
      superAdminEmpresasService.listar(),
      superAdminPlanosService.listar(),
    ])
    empresas.value = e.data.data ?? []
    planos.value   = p.data.data ?? []
  } finally {
    loading.value = false
  }
}

function abrirNova() {
  editandoId.value = null
  Object.assign(form, { nome: '', cnpj: '', telefone: '', email: '', responsavel: '', plano_id: '', status: 'ativo', admin_nome: '', admin_email: '', admin_password: '' })
  dialogVisivel.value = true
}

function abrirEditar(empresa) {
  editandoId.value = empresa.id
  Object.assign(form, { nome: empresa.nome, cnpj: empresa.cnpj, telefone: empresa.telefone ?? '', email: empresa.email ?? '', responsavel: empresa.responsavel ?? '', plano_id: empresa.plano_id ?? '', status: empresa.status, admin_nome: '', admin_email: '', admin_password: '' })
  dialogVisivel.value = true
}

async function salvar() {
  salvando.value = true
  try {
    if (editandoId.value) {
      const { data } = await superAdminEmpresasService.atualizar(editandoId.value, {
        nome: form.nome, cnpj: form.cnpj, telefone: form.telefone, email: form.email,
        responsavel: form.responsavel, plano_id: form.plano_id || null, status: form.status,
      })
      const idx = empresas.value.findIndex(e => e.id === editandoId.value)
      if (idx >= 0) empresas.value[idx] = data.data
    } else {
      const { data } = await superAdminEmpresasService.criar({ ...form, plano_id: form.plano_id || null })
      empresas.value.unshift(data.data)
    }
    uiStore.addToast({ severity: 'success', summary: editandoId.value ? 'Empresa atualizada' : 'Empresa e admin criados', life: 3000 })
    dialogVisivel.value = false
  } catch (e) {
    const msg = e?.response?.data?.message ?? 'Erro ao salvar'
    uiStore.addToast({ severity: 'error', summary: msg, life: 4000 })
  } finally {
    salvando.value = false
  }
}

async function remover(empresa) {
  if (!confirm(`Remover "${empresa.nome}"? Esta ação não pode ser desfeita.`)) return
  try {
    await superAdminEmpresasService.excluir(empresa.id)
    empresas.value = empresas.value.filter(e => e.id !== empresa.id)
    uiStore.addToast({ severity: 'success', summary: 'Empresa removida', life: 2500 })
  } catch {
    uiStore.addToast({ severity: 'error', summary: 'Erro ao remover', life: 3000 })
  }
}

onMounted(carregar)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Empresas</h1>
        <p class="page-subtitle">Gerencie todas as empresas do sistema</p>
      </div>
      <button class="btn-primary" @click="abrirNova">
        <i class="pi pi-plus" /> Nova empresa
      </button>
    </div>

    <div v-if="loading" class="loading-state">Carregando...</div>

    <div v-else class="table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Empresa</th><th>CNPJ</th><th>Plano</th><th>Usuários</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="empresa in empresas" :key="empresa.id">
            <td>
              <div class="cell-primary">{{ empresa.nome }}</div>
              <div class="cell-secondary">{{ empresa.email }}</div>
            </td>
            <td>{{ empresa.cnpj }}</td>
            <td>{{ empresa.plano?.nome ?? '—' }}</td>
            <td>{{ empresa.users_count ?? '—' }}</td>
            <td>
              <span :class="['badge', empresa.status === 'ativo' ? 'badge--ativo' : 'badge--inativo']">
                {{ empresa.status }}
              </span>
            </td>
            <td class="actions">
              <button class="btn-icon" @click="abrirEditar(empresa)" title="Editar"><i class="pi pi-pencil" /></button>
              <button class="btn-icon btn-icon--danger" @click="remover(empresa)" title="Remover"><i class="pi pi-trash" /></button>
            </td>
          </tr>
          <tr v-if="!empresas.length">
            <td colspan="6" class="empty-state">Nenhuma empresa cadastrada</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Dialog -->
    <div v-if="dialogVisivel" class="dialog-overlay" @click.self="dialogVisivel = false">
      <div class="dialog">
        <div class="dialog-header">
          <h2>{{ editandoId ? 'Editar empresa' : 'Nova empresa' }}</h2>
          <button class="btn-close" @click="dialogVisivel = false"><i class="pi pi-times" /></button>
        </div>
        <div class="dialog-body">
          <div class="form-row">
            <div class="form-group">
              <label>Nome da empresa *</label>
              <input v-model="form.nome" class="form-input" placeholder="Razão social" />
            </div>
            <div class="form-group">
              <label>CNPJ *</label>
              <input v-model="form.cnpj" class="form-input" placeholder="00.000.000/0001-00" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>E-mail</label>
              <input v-model="form.email" class="form-input" type="email" />
            </div>
            <div class="form-group">
              <label>Telefone</label>
              <input v-model="form.telefone" class="form-input" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Responsável</label>
              <input v-model="form.responsavel" class="form-input" />
            </div>
            <div class="form-group">
              <label>Plano</label>
              <select v-model="form.plano_id" class="form-input">
                <option value="">Sem plano</option>
                <option v-for="p in planos" :key="p.id" :value="p.id">{{ p.nome }}</option>
              </select>
            </div>
          </div>

          <template v-if="!editandoId">
            <div class="section-title">Usuário admin da empresa</div>
            <div class="form-row">
              <div class="form-group">
                <label>Nome do admin *</label>
                <input v-model="form.admin_nome" class="form-input" />
              </div>
              <div class="form-group">
                <label>E-mail do admin *</label>
                <input v-model="form.admin_email" class="form-input" type="email" />
              </div>
            </div>
            <div class="form-group">
              <label>Senha inicial *</label>
              <input v-model="form.admin_password" class="form-input" type="password" />
            </div>
          </template>

          <div v-if="editandoId" class="form-group">
            <label>Status</label>
            <select v-model="form.status" class="form-input">
              <option value="ativo">Ativo</option>
              <option value="inativo">Inativo</option>
            </select>
          </div>
        </div>
        <div class="dialog-footer">
          <button class="btn-secondary" @click="dialogVisivel = false">Cancelar</button>
          <button class="btn-primary" @click="salvar" :disabled="salvando">
            {{ salvando ? 'Salvando...' : 'Salvar' }}
          </button>
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
.cell-primary { font-weight: 500; }
.cell-secondary { font-size: 0.8rem; color: var(--color-text-muted); }
.badge { padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
.badge--ativo { background: rgba(34,197,94,0.15); color: #22c55e; }
.badge--inativo { background: rgba(107,114,128,0.15); color: #6b7280; }
.actions { display: flex; gap: 0.5rem; }
.btn-icon { background: none; border: 1px solid var(--color-border); color: var(--color-text-muted); cursor: pointer; padding: 0.35rem 0.5rem; border-radius: 6px; transition: all 0.15s; }
.btn-icon:hover { color: var(--color-text); border-color: var(--color-text-muted); }
.btn-icon--danger:hover { color: #ef4444; border-color: #ef4444; }
.btn-primary { display: flex; align-items: center; gap: 0.4rem; background: var(--color-gold); color: #111; border: none; padding: 0.6rem 1.25rem; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.9rem; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary { background: none; border: 1px solid var(--color-border); color: var(--color-text); padding: 0.6rem 1.25rem; border-radius: 6px; cursor: pointer; font-size: 0.9rem; }
.empty-state { text-align: center; color: var(--color-text-muted); padding: 2rem; }
.loading-state { text-align: center; color: var(--color-text-muted); padding: 3rem; }
.section-title { font-size: 0.85rem; font-weight: 600; color: var(--color-gold); text-transform: uppercase; letter-spacing: 0.05em; margin: 1rem 0 0.5rem; border-top: 1px solid var(--color-border); padding-top: 1rem; }
.dialog-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 100; }
.dialog { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 10px; width: 560px; max-height: 90vh; overflow-y: auto; }
.dialog-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); }
.dialog-header h2 { margin: 0; font-size: 1.1rem; color: var(--color-text); }
.btn-close { background: none; border: none; color: var(--color-text-muted); cursor: pointer; font-size: 1rem; }
.dialog-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.dialog-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-group label { font-size: 0.8rem; color: var(--color-text-muted); }
.form-input { background: var(--color-surface-2); border: 1px solid var(--color-border); color: var(--color-text); padding: 0.6rem 0.75rem; border-radius: 6px; font-size: 0.9rem; }
.form-input:focus { outline: none; border-color: var(--color-gold); }
</style>
