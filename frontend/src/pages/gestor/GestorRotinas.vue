<script setup>
// GestorRotinas usa o mesmo AdminRotinas — dados já filtrados pelo setor no backend
// Só muda o título e remove o seletor de setor
import { ref, computed, onMounted } from 'vue'
import { useRotinas } from '../../composables/useRotinas.js'
import { usuariosService } from '../../services/usuarios.service.js'
import AppConfirmDialog from '../../components/ui/AppConfirmDialog.vue'
import { useAuthStore } from '../../stores/auth.store.js'

const { rotinas, loading, salvando, preview, buscar, salvar, remover, buscarPreview } = useRotinas()
const authStore = useAuthStore()

const colaboradoresSetor = ref([])

const isMobile = ref(window.innerWidth <= 640)
window.addEventListener('resize', () => { isMobile.value = window.innerWidth <= 640 })

const filtros = ref({ status: '', frequencia: '', busca: '' })
const dialogAberto = ref(false)
const confirmAberto = ref(false)
const rotinaParaExcluir = ref(null)
const removendo = ref(false)
const form = ref(criarFormVazio())
const editandoId = ref(null)

function criarFormVazio() {
  return {
    titulo: '', descricao: '', frequencia: 'diaria',
    dias_semana: [], dias_mes: [], horario_previsto: '08:00',
    foto_obrigatoria: false, so_camera: true, justif_obrigatoria: false,
    data_inicio: new Date().toISOString().slice(0, 10), data_fim: '',
    colaborador_ids: [],
  }
}

const diasSemanaOpcoes = [
  { label: 'Dom', value: 0 }, { label: 'Seg', value: 1 }, { label: 'Ter', value: 2 },
  { label: 'Qua', value: 3 }, { label: 'Qui', value: 4 }, { label: 'Sex', value: 5 },
  { label: 'Sáb', value: 6 },
]
const diasMesOpcoes = Array.from({ length: 31 }, (_, i) => ({ label: String(i + 1), value: i + 1 }))
const frequenciaOpcoes = [
  { label: 'Diária', value: 'diaria' }, { label: 'Semanal', value: 'semanal' },
  { label: 'Mensal', value: 'mensal' }, { label: 'Por turno', value: 'turno' },
]

const rotinasFiltradas = computed(() => {
  let lista = rotinas.value
  if (filtros.value.status) lista = lista.filter((r) => r.status === filtros.value.status)
  if (filtros.value.frequencia) lista = lista.filter((r) => r.frequencia === filtros.value.frequencia)
  if (filtros.value.busca) {
    const q = filtros.value.busca.toLowerCase()
    lista = lista.filter((r) => r.titulo.toLowerCase().includes(q))
  }
  return lista
})

function abrirCriar() { form.value = criarFormVazio(); editandoId.value = null; preview.value = []; dialogAberto.value = true }
function abrirEditar(rotina) {
  form.value = {
    ...rotina,
    dias_semana: rotina.dias_semana ?? [],
    dias_mes: rotina.dias_mes ?? [],
    data_fim: rotina.data_fim ?? '',
    colaborador_ids: rotina.colaborador_ids ?? [],
  }
  editandoId.value = rotina.id
  buscarPreview(rotina.id)
  dialogAberto.value = true
}
async function salvarRotina() {
  const ok = await salvar(form.value, editandoId.value)
  if (ok) dialogAberto.value = false
}
function confirmarExcluir(rotina) { rotinaParaExcluir.value = rotina; confirmAberto.value = true }
async function executarExcluir() {
  removendo.value = true
  await remover(rotinaParaExcluir.value.id)
  removendo.value = false
  confirmAberto.value = false
}

const labelFrequencia = { diaria: 'Diária', semanal: 'Semanal', mensal: 'Mensal', turno: 'Turno' }

async function carregarColaboradores() {
  try {
    const { data } = await usuariosService.listarColaboradores({ per_page: 100 })
    colaboradoresSetor.value = data.data ?? []
  } catch {
    colaboradoresSetor.value = []
  }
}

onMounted(() => { buscar(); carregarColaboradores() })
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Rotinas do meu setor</h1>
        <p class="page-sub">{{ authStore.user?.setor_nome ?? '' }}</p>
      </div>
      <button class="btn-primary" @click="abrirCriar"><i class="pi pi-plus" /> Nova rotina</button>
    </div>

    <div class="filtros">
      <input v-model="filtros.busca" class="input-busca" placeholder="Buscar por título..." />
      <select v-model="filtros.frequencia" class="select-filtro">
        <option value="">Todas as frequências</option>
        <option v-for="f in frequenciaOpcoes" :key="f.value" :value="f.value">{{ f.label }}</option>
      </select>
      <select v-model="filtros.status" class="select-filtro">
        <option value="">Todos os status</option>
        <option value="ativa">Ativa</option>
        <option value="inativa">Inativa</option>
      </select>
    </div>

    <div v-if="loading" class="loading-state"><i class="pi pi-spin pi-spinner" /> Carregando...</div>

    <!-- Tabela desktop -->
    <div v-else-if="!isMobile" class="table-wrapper">
      <table class="data-table">
        <thead><tr>
          <th>Título</th><th>Frequência</th><th>Horário</th><th>Foto</th><th>Status</th><th></th>
        </tr></thead>
        <tbody>
          <tr v-if="rotinasFiltradas.length === 0"><td colspan="6" class="empty-row">Nenhuma rotina encontrada</td></tr>
          <tr v-for="r in rotinasFiltradas" :key="r.id" class="data-row">
            <td class="td-titulo">{{ r.titulo }}</td>
            <td>{{ labelFrequencia[r.frequencia] }}</td>
            <td>{{ r.horario_previsto }}</td>
            <td>
              <span v-if="r.foto_obrigatoria" class="badge badge-foto"><i class="pi pi-camera" /> Sim</span>
              <span v-else class="td-muted">—</span>
            </td>
            <td>
              <span class="badge" :class="r.status === 'ativa' ? 'badge-ativa' : 'badge-inativa'">{{ r.status }}</span>
            </td>
            <td class="td-actions">
              <button class="btn-icon" @click="abrirEditar(r)"><i class="pi pi-pencil" /></button>
              <button class="btn-icon btn-icon--danger" @click="confirmarExcluir(r)"><i class="pi pi-trash" /></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Cards mobile -->
    <div v-else class="cards-lista">
      <div v-if="rotinasFiltradas.length === 0" class="empty-state">Nenhuma rotina encontrada</div>
      <div v-for="r in rotinasFiltradas" :key="r.id" class="rotina-card">
        <div class="card-top">
          <span class="card-titulo">{{ r.titulo }}</span>
          <span class="badge" :class="r.status === 'ativa' ? 'badge-ativa' : 'badge-inativa'">{{ r.status }}</span>
        </div>
        <div class="card-info">
          <span>{{ labelFrequencia[r.frequencia] }}</span><span class="sep">·</span><span>{{ r.horario_previsto }}</span>
        </div>
        <div class="card-actions">
          <button class="btn-ghost" @click="abrirEditar(r)"><i class="pi pi-pencil" /> Editar</button>
          <button class="btn-ghost btn-ghost--danger" @click="confirmarExcluir(r)"><i class="pi pi-trash" /> Excluir</button>
        </div>
      </div>
    </div>

    <!-- Dialog -->
    <Teleport to="body">
      <Transition name="overlay">
        <div v-if="dialogAberto" class="overlay" @click.self="dialogAberto = false">
          <div class="dialog dialog-lg">
            <div class="dialog-header">
              <h2 class="dialog-titulo">{{ editandoId ? 'Editar rotina' : 'Nova rotina' }}</h2>
              <button class="btn-close" @click="dialogAberto = false"><i class="pi pi-times" /></button>
            </div>
            <div class="dialog-body">
              <div class="form-grid">
                <div class="field field-full">
                  <label>Título *</label>
                  <input v-model="form.titulo" class="input" placeholder="Ex: Limpeza dos equipamentos" />
                </div>
                <div class="field">
                  <label>Horário previsto *</label>
                  <input v-model="form.horario_previsto" type="time" class="input" />
                </div>
                <div class="field">
                  <label>Frequência *</label>
                  <select v-model="form.frequencia" class="input">
                    <option v-for="f in frequenciaOpcoes" :key="f.value" :value="f.value">{{ f.label }}</option>
                  </select>
                </div>
                <div v-if="form.frequencia === 'semanal'" class="field field-full">
                  <label>Dias da semana *</label>
                  <div class="checkbox-group">
                    <label v-for="dia in diasSemanaOpcoes" :key="dia.value" class="checkbox-pill" :class="{ 'checkbox-pill--active': form.dias_semana.includes(dia.value) }">
                      <input type="checkbox" :value="dia.value" v-model="form.dias_semana" style="display:none" />
                      {{ dia.label }}
                    </label>
                  </div>
                </div>
                <div v-if="form.frequencia === 'mensal'" class="field field-full">
                  <label>Dias do mês *</label>
                  <div class="dias-mes-grid">
                    <label v-for="dia in diasMesOpcoes" :key="dia.value" class="checkbox-pill" :class="{ 'checkbox-pill--active': form.dias_mes.includes(dia.value) }">
                      <input type="checkbox" :value="dia.value" v-model="form.dias_mes" style="display:none" />{{ dia.label }}
                    </label>
                  </div>
                </div>
                <div class="field"><label>Data início *</label><input v-model="form.data_inicio" type="date" class="input" /></div>
                <div class="field"><label>Data fim</label><input v-model="form.data_fim" type="date" class="input" /></div>
                <div class="field field-full"><label>Descrição</label><textarea v-model="form.descricao" class="input textarea" rows="2" /></div>
                <div class="field field-full toggles-row">
                  <label class="toggle-label">
                    <span>Foto obrigatória</span>
                    <div class="toggle" :class="{ 'toggle--on': form.foto_obrigatoria }" @click="form.foto_obrigatoria = !form.foto_obrigatoria"><div class="toggle-thumb" /></div>
                  </label>
                  <label class="toggle-label">
                    <span>Justificativa obrigatória</span>
                    <div class="toggle" :class="{ 'toggle--on': form.justif_obrigatoria }" @click="form.justif_obrigatoria = !form.justif_obrigatoria"><div class="toggle-thumb" /></div>
                  </label>
                </div>
                <div v-if="editandoId" class="field">
                  <label>Status</label>
                  <select v-model="form.status" class="input"><option value="ativa">Ativa</option><option value="inativa">Inativa</option></select>
                </div>

                <div v-if="colaboradoresSetor.length" class="field field-full">
                  <label>Colaboradores <span class="label-hint">(vazio = todos do setor)</span></label>
                  <div class="checkbox-group">
                    <label
                      v-for="c in colaboradoresSetor"
                      :key="c.id"
                      class="checkbox-pill"
                      :class="{ 'checkbox-pill--active': form.colaborador_ids.includes(c.id) }"
                    >
                      <input type="checkbox" :value="c.id" v-model="form.colaborador_ids" style="display:none" />
                      {{ c.nome }}
                    </label>
                  </div>
                </div>
              </div>
              <div v-if="editandoId && preview.length" class="preview-box">
                <p class="preview-titulo"><i class="pi pi-calendar" /> Próximas gerações</p>
                <div class="preview-lista">
                  <span v-for="p in preview" :key="p.data" class="preview-item">{{ p.data }}</span>
                </div>
              </div>
            </div>
            <div class="dialog-footer">
              <button class="btn-ghost" @click="dialogAberto = false">Cancelar</button>
              <button class="btn-primary" :disabled="salvando" @click="salvarRotina">
                <i v-if="salvando" class="pi pi-spin pi-spinner" />{{ salvando ? 'Salvando...' : 'Salvar' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <AppConfirmDialog v-model:visivel="confirmAberto" titulo="Excluir rotina" variante="danger" label-confirmar="Excluir" :carregando="removendo" @confirmar="executarExcluir">
      Deseja excluir <strong>{{ rotinaParaExcluir?.titulo }}</strong>?
    </AppConfirmDialog>
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1.25rem; }
.page-header { display: flex; align-items: flex-start; justify-content: space-between; }
.page-title { font-size: 1.375rem; font-weight: 700; color: var(--color-text); margin: 0; }
.page-sub { font-size: 0.8125rem; color: var(--color-text-muted); margin: 0.15rem 0 0; }
.filtros { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.input-busca, .select-filtro { background: var(--color-surface-2); border: 1px solid var(--color-border); color: var(--color-text); padding: 0.5rem 0.75rem; border-radius: 8px; font-size: 0.875rem; outline: none; }
.input-busca { min-width: 180px; flex: 1; }
.input-busca:focus, .select-filtro:focus { border-color: var(--color-gold); }
.loading-state { color: var(--color-text-muted); padding: 2rem; text-align: center; }
.table-wrapper { overflow-x: auto; border-radius: 10px; border: 1px solid var(--color-border); }
.data-table { width: 100%; border-collapse: collapse; background: var(--color-surface); }
.data-table th { background: var(--color-surface-2); color: var(--color-text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; padding: 0.75rem 1rem; text-align: left; }
.data-table td { padding: 0.875rem 1rem; border-top: 1px solid var(--color-border); color: var(--color-text); font-size: 0.875rem; }
.data-row:hover { background: rgba(201,168,76,0.04); }
.td-titulo { font-weight: 500; }
.td-muted { color: var(--color-text-muted); }
.empty-row { text-align: center; color: var(--color-text-muted); padding: 2rem; }
.badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.6rem; border-radius: 99px; font-size: 0.75rem; font-weight: 600; }
.badge-ativa { background: rgba(34,197,94,0.15); color: #22C55E; }
.badge-inativa { background: rgba(107,114,128,0.15); color: #9CA3AF; }
.badge-foto { background: rgba(201,168,76,0.15); color: var(--color-gold); }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; background: var(--color-gold); color: #111; font-weight: 600; padding: 0.55rem 1.1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.875rem; }
.btn-primary:hover { background: var(--color-gold-light); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-ghost { display: inline-flex; align-items: center; gap: 0.4rem; background: transparent; color: var(--color-text-muted); font-size: 0.875rem; padding: 0.5rem 0.875rem; border: 1px solid var(--color-border); border-radius: 8px; cursor: pointer; }
.btn-ghost:hover { color: var(--color-text); border-color: var(--color-text-muted); }
.btn-ghost--danger:hover { color: #EF4444; border-color: #EF4444; }
.btn-icon { background: none; border: none; cursor: pointer; padding: 0.4rem; color: var(--color-text-muted); border-radius: 6px; }
.btn-icon:hover { color: var(--color-gold); background: rgba(201,168,76,0.1); }
.btn-icon--danger:hover { color: #EF4444; background: rgba(239,68,68,0.1); }
.td-actions { display: flex; gap: 0.25rem; }
.cards-lista { display: flex; flex-direction: column; gap: 0.75rem; }
.rotina-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 10px; padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem; }
.card-top { display: flex; align-items: center; justify-content: space-between; }
.card-titulo { font-weight: 600; color: var(--color-text); }
.card-info { font-size: 0.8rem; color: var(--color-text-muted); display: flex; gap: 0.4rem; }
.sep { opacity: 0.4; }
.card-actions { display: flex; gap: 0.5rem; }
.empty-state { text-align: center; color: var(--color-text-muted); padding: 2rem; }
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.dialog { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; width: 100%; max-width: 520px; max-height: 90vh; display: flex; flex-direction: column; }
.dialog-lg { max-width: 620px; }
.dialog-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); }
.dialog-titulo { margin: 0; font-size: 1.125rem; font-weight: 600; color: var(--color-text); }
.btn-close { background: none; border: none; cursor: pointer; color: var(--color-text-muted); }
.dialog-body { overflow-y: auto; padding: 1.5rem; flex: 1; display: flex; flex-direction: column; gap: 1rem; }
.dialog-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.375rem; }
.field-full { grid-column: 1 / -1; }
.field label { font-size: 0.8rem; color: var(--color-text-muted); font-weight: 500; }
.label-hint { font-weight: 400; font-style: italic; }
.input { background: var(--color-surface-2); border: 1px solid var(--color-border); color: var(--color-text); padding: 0.5rem 0.75rem; border-radius: 8px; font-size: 0.875rem; outline: none; width: 100%; }
.input:focus { border-color: var(--color-gold); }
.textarea { resize: vertical; min-height: 60px; font-family: inherit; }
.checkbox-group { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.dias-mes-grid { display: flex; flex-wrap: wrap; gap: 0.375rem; }
.checkbox-pill { padding: 0.3rem 0.65rem; border-radius: 99px; font-size: 0.8rem; cursor: pointer; border: 1px solid var(--color-border); color: var(--color-text-muted); user-select: none; }
.checkbox-pill--active { border-color: var(--color-gold); color: var(--color-gold); background: rgba(201,168,76,0.12); }
.toggles-row { display: flex; flex-direction: column; gap: 0.75rem; }
.toggle-label { display: flex; align-items: center; justify-content: space-between; }
.toggle-label > span { font-size: 0.875rem; color: var(--color-text); }
.toggle { width: 42px; height: 24px; background: var(--color-border); border-radius: 99px; cursor: pointer; position: relative; transition: background 0.2s; flex-shrink: 0; }
.toggle--on { background: var(--color-gold); }
.toggle-thumb { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; background: white; border-radius: 50%; transition: transform 0.2s; }
.toggle--on .toggle-thumb { transform: translateX(18px); }
.preview-box { background: var(--color-surface-2); border: 1px solid var(--color-border); border-radius: 8px; padding: 0.875rem; }
.preview-titulo { margin: 0 0 0.5rem; font-size: 0.8rem; color: var(--color-text-muted); font-weight: 600; }
.preview-lista { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.preview-item { background: rgba(201,168,76,0.1); border: 1px solid rgba(201,168,76,0.3); color: var(--color-text); font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 6px; }
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.2s; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }
@media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } .field-full { grid-column: 1; } }
</style>
