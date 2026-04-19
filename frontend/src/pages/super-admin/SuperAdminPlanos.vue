<script setup>
import { ref, reactive, onMounted } from 'vue'
import { superAdminPlanosService } from '../../services/super-admin.service.js'
import { useUiStore } from '../../stores/ui.store.js'

const uiStore  = useUiStore()
const planos   = ref([])
const loading  = ref(false)
const salvando = ref(false)
const dialogVisivel = ref(false)
const editandoId    = ref(null)

const form = reactive({ nome: '', limite_usuarios: 10, limite_setores: 5, limite_rotinas: 50, ativo: true })

async function carregar() {
  loading.value = true
  try {
    const { data } = await superAdminPlanosService.listar()
    planos.value = data.data ?? []
  } finally { loading.value = false }
}

function abrirNovo() {
  editandoId.value = null
  Object.assign(form, { nome: '', limite_usuarios: 10, limite_setores: 5, limite_rotinas: 50, ativo: true })
  dialogVisivel.value = true
}

function abrirEditar(plano) {
  editandoId.value = plano.id
  Object.assign(form, { nome: plano.nome, limite_usuarios: plano.limite_usuarios, limite_setores: plano.limite_setores, limite_rotinas: plano.limite_rotinas, ativo: plano.ativo })
  dialogVisivel.value = true
}

async function salvar() {
  salvando.value = true
  try {
    if (editandoId.value) {
      const { data } = await superAdminPlanosService.atualizar(editandoId.value, { ...form })
      const idx = planos.value.findIndex(p => p.id === editandoId.value)
      if (idx >= 0) planos.value[idx] = data.data
    } else {
      const { data } = await superAdminPlanosService.criar({ ...form })
      planos.value.unshift(data.data)
    }
    uiStore.addToast({ severity: 'success', summary: 'Plano salvo', life: 2500 })
    dialogVisivel.value = false
  } catch (e) {
    uiStore.addToast({ severity: 'error', summary: e?.response?.data?.message ?? 'Erro ao salvar', life: 4000 })
  } finally { salvando.value = false }
}

onMounted(carregar)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Planos</h1>
        <p class="page-subtitle">Configure os planos de acesso do sistema</p>
      </div>
      <button class="btn-primary" @click="abrirNovo"><i class="pi pi-plus" /> Novo plano</button>
    </div>

    <div v-if="loading" class="loading-state">Carregando...</div>
    <div v-else class="cards-grid">
      <div v-for="plano in planos" :key="plano.id" class="plano-card">
        <div class="plano-header">
          <span class="plano-nome">{{ plano.nome }}</span>
          <span :class="['badge', plano.ativo ? 'badge--ativo' : 'badge--inativo']">{{ plano.ativo ? 'Ativo' : 'Inativo' }}</span>
        </div>
        <div class="plano-limites">
          <div class="limite-item"><i class="pi pi-users" /><span>{{ plano.limite_usuarios }} usuários</span></div>
          <div class="limite-item"><i class="pi pi-sitemap" /><span>{{ plano.limite_setores }} setores</span></div>
          <div class="limite-item"><i class="pi pi-list-check" /><span>{{ plano.limite_rotinas }} rotinas</span></div>
        </div>
        <div class="plano-footer">
          <span class="empresas-count">{{ plano.empresas_count ?? 0 }} empresa(s)</span>
          <button class="btn-icon" @click="abrirEditar(plano)"><i class="pi pi-pencil" /></button>
        </div>
      </div>
      <div v-if="!planos.length" class="empty-state">Nenhum plano cadastrado</div>
    </div>

    <div v-if="dialogVisivel" class="dialog-overlay" @click.self="dialogVisivel = false">
      <div class="dialog">
        <div class="dialog-header">
          <h2>{{ editandoId ? 'Editar plano' : 'Novo plano' }}</h2>
          <button class="btn-close" @click="dialogVisivel = false"><i class="pi pi-times" /></button>
        </div>
        <div class="dialog-body">
          <div class="form-group">
            <label>Nome do plano *</label>
            <input v-model="form.nome" class="form-input" placeholder="Ex: Básico, Pro, Enterprise" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Limite de usuários</label>
              <input v-model.number="form.limite_usuarios" class="form-input" type="number" min="1" />
            </div>
            <div class="form-group">
              <label>Limite de setores</label>
              <input v-model.number="form.limite_setores" class="form-input" type="number" min="1" />
            </div>
          </div>
          <div class="form-group">
            <label>Limite de rotinas</label>
            <input v-model.number="form.limite_rotinas" class="form-input" type="number" min="1" />
          </div>
          <div class="form-group form-group--check">
            <input id="ativo" v-model="form.ativo" type="checkbox" />
            <label for="ativo">Plano ativo</label>
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
.cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
.plano-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 8px; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; }
.plano-header { display: flex; justify-content: space-between; align-items: center; }
.plano-nome { font-weight: 600; font-size: 1.05rem; color: var(--color-text); }
.plano-limites { display: flex; flex-direction: column; gap: 0.4rem; }
.limite-item { display: flex; align-items: center; gap: 0.5rem; color: var(--color-text-muted); font-size: 0.875rem; }
.plano-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--color-border); padding-top: 0.75rem; }
.empresas-count { font-size: 0.8rem; color: var(--color-text-muted); }
.badge { padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.badge--ativo { background: rgba(34,197,94,0.15); color: #22c55e; }
.badge--inativo { background: rgba(107,114,128,0.15); color: #6b7280; }
.btn-primary { display: flex; align-items: center; gap: 0.4rem; background: var(--color-gold); color: #111; border: none; padding: 0.6rem 1.25rem; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.9rem; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary { background: none; border: 1px solid var(--color-border); color: var(--color-text); padding: 0.6rem 1.25rem; border-radius: 6px; cursor: pointer; }
.btn-icon { background: none; border: 1px solid var(--color-border); color: var(--color-text-muted); cursor: pointer; padding: 0.35rem 0.5rem; border-radius: 6px; }
.btn-icon:hover { color: var(--color-text); }
.loading-state, .empty-state { color: var(--color-text-muted); padding: 2rem; text-align: center; }
.dialog-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 100; }
.dialog { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 10px; width: 480px; }
.dialog-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); }
.dialog-header h2 { margin: 0; font-size: 1.1rem; color: var(--color-text); }
.btn-close { background: none; border: none; color: var(--color-text-muted); cursor: pointer; }
.dialog-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.dialog-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-group label { font-size: 0.8rem; color: var(--color-text-muted); }
.form-group--check { flex-direction: row; align-items: center; gap: 0.5rem; }
.form-group--check label { font-size: 0.9rem; color: var(--color-text); }
.form-input { background: var(--color-surface-2); border: 1px solid var(--color-border); color: var(--color-text); padding: 0.6rem 0.75rem; border-radius: 6px; font-size: 0.9rem; }
.form-input:focus { outline: none; border-color: var(--color-gold); }
</style>
