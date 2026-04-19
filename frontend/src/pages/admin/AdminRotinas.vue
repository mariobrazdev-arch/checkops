<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRotinas } from '../../composables/useRotinas.js'
import { useSetores } from '../../composables/useSetores.js'
import { usuariosService } from '../../services/usuarios.service.js'
import AppConfirmDialog from '../../components/ui/AppConfirmDialog.vue'

const { rotinas, loading, salvando, preview, buscar, salvar, remover, buscarPreview } = useRotinas()
const { setores, buscar: buscarSetores } = useSetores()

const colaboradoresSetor = ref([])

// ─── State da página ────────────────────────────────────────────────────────
const isMobile = ref(window.innerWidth <= 640)
window.addEventListener('resize', () => { isMobile.value = window.innerWidth <= 640 })

const filtros = ref({ setor_id: '', status: '', frequencia: '', busca: '' })
const dialogAberto = ref(false)
const confirmAberto = ref(false)
const rotinaParaExcluir = ref(null)
const removendo = ref(false)

const form = ref(criarFormVazio())
const editandoId = ref(null)

function criarFormVazio() {
  return {
    titulo: '',
    descricao: '',
    setor_id: '',
    frequencia: 'diaria',
    dias_semana: [],
    dias_mes: [],
    horario_previsto: '08:00',
    foto_obrigatoria: false,
    so_camera: true,
    justif_obrigatoria: false,
    data_inicio: new Date().toISOString().slice(0, 10),
    data_fim: '',
    colaborador_ids: [],
  }
}

async function carregarColaboradoresSetor(setorId) {
  if (!setorId) { colaboradoresSetor.value = []; return }
  try {
    const { data } = await usuariosService.listar({ setor_id: setorId, perfil: 'colaborador', per_page: 100 })
    colaboradoresSetor.value = data.data ?? []
  } catch {
    colaboradoresSetor.value = []
  }
}

watch(() => form.value.setor_id, (id) => {
  form.value.colaborador_ids = []
  carregarColaboradoresSetor(id)
})

const diasSemanaOpcoes = [
  { label: 'Dom', value: 0 }, { label: 'Seg', value: 1 }, { label: 'Ter', value: 2 },
  { label: 'Qua', value: 3 }, { label: 'Qui', value: 4 }, { label: 'Sex', value: 5 },
  { label: 'Sáb', value: 6 },
]
const diasMesOpcoes = Array.from({ length: 31 }, (_, i) => ({ label: String(i + 1), value: i + 1 }))

const frequenciaOpcoes = [
  { label: 'Diária', value: 'diaria' },
  { label: 'Semanal', value: 'semanal' },
  { label: 'Mensal', value: 'mensal' },
  { label: 'Por turno', value: 'turno' },
]
const statusOpcoes = [
  { label: 'Ativa', value: 'ativa' },
  { label: 'Inativa', value: 'inativa' },
]

// ─── Preview automático ──────────────────────────────────────────────────────
const previewCarregando = ref(false)
watch(
  () => [form.value.frequencia, form.value.data_inicio, form.value.dias_semana, form.value.dias_mes],
  () => {
    if (editandoId.value && form.value.frequencia && form.value.data_inicio) {
      previewCarregando.value = true
      buscarPreview(editandoId.value).finally(() => { previewCarregando.value = false })
    }
  },
  { deep: true }
)

// ─── Filtros reativos ────────────────────────────────────────────────────────
const rotinasFiltradas = computed(() => {
  let lista = rotinas.value
  if (filtros.value.setor_id) lista = lista.filter((r) => r.setor_id === filtros.value.setor_id)
  if (filtros.value.status)   lista = lista.filter((r) => r.status === filtros.value.status)
  if (filtros.value.frequencia) lista = lista.filter((r) => r.frequencia === filtros.value.frequencia)
  if (filtros.value.busca) {
    const q = filtros.value.busca.toLowerCase()
    lista = lista.filter((r) => r.titulo.toLowerCase().includes(q))
  }
  return lista
})

// ─── CRUD ─────────────────────────────────────────────────────────────────────
function abrirCriar() {
  form.value = criarFormVazio()
  editandoId.value = null
  preview.value = []
  dialogAberto.value = true
}

function abrirEditar(rotina) {
  form.value = {
    titulo: rotina.titulo,
    descricao: rotina.descricao ?? '',
    setor_id: rotina.setor_id,
    frequencia: rotina.frequencia,
    dias_semana: rotina.dias_semana ?? [],
    dias_mes: rotina.dias_mes ?? [],
    horario_previsto: rotina.horario_previsto,
    foto_obrigatoria: rotina.foto_obrigatoria,
    so_camera: rotina.so_camera,
    justif_obrigatoria: rotina.justif_obrigatoria,
    data_inicio: rotina.data_inicio,
    data_fim: rotina.data_fim ?? '',
    status: rotina.status,
    colaborador_ids: rotina.colaborador_ids ?? [],
  }
  editandoId.value = rotina.id
  buscarPreview(rotina.id)
  carregarColaboradoresSetor(rotina.setor_id)
  dialogAberto.value = true
}

async function salvarRotina() {
  const ok = await salvar(form.value, editandoId.value)
  if (ok) dialogAberto.value = false
}

function confirmarExcluir(rotina) {
  rotinaParaExcluir.value = rotina
  confirmAberto.value = true
}

async function executarExcluir() {
  removendo.value = true
  await remover(rotinaParaExcluir.value.id)
  removendo.value = false
  confirmAberto.value = false
}

const labelFrequencia = { diaria: 'Diária', semanal: 'Semanal', mensal: 'Mensal', turno: 'Turno' }
const labelDiasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']

function resumoDias(rotina) {
  if (rotina.frequencia === 'semanal' && rotina.dias_semana?.length) {
    return rotina.dias_semana.map((d) => labelDiasSemana[d]).join(', ')
  }
  if (rotina.frequencia === 'mensal' && rotina.dias_mes?.length) {
    return 'Dias ' + rotina.dias_mes.join(', ')
  }
  return '—'
}

onMounted(() => {
  buscar()
  buscarSetores()
})
</script>

<template>
  <div class="page">
    <!-- Header -->
    <div class="page-header">
      <h1 class="page-title">Rotinas</h1>
      <button class="btn-primary" @click="abrirCriar">
        <i class="pi pi-plus" /> Nova rotina
      </button>
    </div>

    <!-- Filtros -->
    <div class="filtros">
      <input v-model="filtros.busca" class="input-busca" placeholder="Buscar por título..." />
      <select v-model="filtros.setor_id" class="select-filtro">
        <option value="">Todos os setores</option>
        <option v-for="s in setores" :key="s.id" :value="s.id">{{ s.nome }}</option>
      </select>
      <select v-model="filtros.frequencia" class="select-filtro">
        <option value="">Todas as frequências</option>
        <option v-for="f in frequenciaOpcoes" :key="f.value" :value="f.value">{{ f.label }}</option>
      </select>
      <select v-model="filtros.status" class="select-filtro">
        <option value="">Todos os status</option>
        <option v-for="s in statusOpcoes" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <i class="pi pi-spin pi-spinner" /> Carregando...
    </div>

    <!-- Tabela (desktop) -->
    <div v-else-if="!isMobile" class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Título</th>
            <th>Setor</th>
            <th>Frequência</th>
            <th>Dias</th>
            <th>Horário</th>
            <th>Foto</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="rotinasFiltradas.length === 0">
            <td colspan="8" class="empty-row">Nenhuma rotina encontrada</td>
          </tr>
          <tr v-for="rotina in rotinasFiltradas" :key="rotina.id" class="data-row">
            <td class="td-titulo">{{ rotina.titulo }}</td>
            <td class="td-muted">{{ rotina.setor?.nome ?? '—' }}</td>
            <td>{{ labelFrequencia[rotina.frequencia] }}</td>
            <td class="td-muted td-small">{{ resumoDias(rotina) }}</td>
            <td>{{ rotina.horario_previsto }}</td>
            <td>
              <span v-if="rotina.foto_obrigatoria" class="badge badge-foto">
                <i class="pi pi-camera" /> Sim
              </span>
              <span v-else class="badge-none">—</span>
            </td>
            <td>
              <span class="badge" :class="rotina.status === 'ativa' ? 'badge-ativa' : 'badge-inativa'">
                {{ rotina.status }}
              </span>
            </td>
            <td class="td-actions">
              <button class="btn-icon" @click="abrirEditar(rotina)" title="Editar">
                <i class="pi pi-pencil" />
              </button>
              <button class="btn-icon btn-icon--danger" @click="confirmarExcluir(rotina)" title="Excluir">
                <i class="pi pi-trash" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Cards (mobile) -->
    <div v-else class="cards-lista">
      <div v-if="rotinasFiltradas.length === 0" class="empty-state">
        Nenhuma rotina encontrada
      </div>
      <div v-for="rotina in rotinasFiltradas" :key="rotina.id" class="rotina-card">
        <div class="card-top">
          <span class="card-titulo">{{ rotina.titulo }}</span>
          <span class="badge" :class="rotina.status === 'ativa' ? 'badge-ativa' : 'badge-inativa'">
            {{ rotina.status }}
          </span>
        </div>
        <div class="card-info">
          <span>{{ rotina.setor?.nome ?? '—' }}</span>
          <span class="sep">·</span>
          <span>{{ labelFrequencia[rotina.frequencia] }}</span>
          <span class="sep">·</span>
          <span>{{ rotina.horario_previsto }}</span>
        </div>
        <div class="card-actions">
          <button class="btn-ghost" @click="abrirEditar(rotina)">
            <i class="pi pi-pencil" /> Editar
          </button>
          <button class="btn-ghost btn-ghost--danger" @click="confirmarExcluir(rotina)">
            <i class="pi pi-trash" /> Excluir
          </button>
        </div>
      </div>
    </div>

    <!-- Dialog criar/editar -->
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
                <!-- Título -->
                <div class="field field-full">
                  <label>Título *</label>
                  <input v-model="form.titulo" class="input" placeholder="Ex: Limpeza dos equipamentos" />
                </div>

                <!-- Setor -->
                <div class="field">
                  <label>Setor *</label>
                  <select v-model="form.setor_id" class="input">
                    <option value="">Selecione...</option>
                    <option v-for="s in setores" :key="s.id" :value="s.id">{{ s.nome }}</option>
                  </select>
                </div>

                <!-- Horário -->
                <div class="field">
                  <label>Horário previsto *</label>
                  <input v-model="form.horario_previsto" type="time" class="input" />
                </div>

                <!-- Frequência -->
                <div class="field">
                  <label>Frequência *</label>
                  <select v-model="form.frequencia" class="input">
                    <option v-for="f in frequenciaOpcoes" :key="f.value" :value="f.value">{{ f.label }}</option>
                  </select>
                </div>

                <!-- Dias da semana (semanal) -->
                <div v-if="form.frequencia === 'semanal'" class="field field-full">
                  <label>Dias da semana *</label>
                  <div class="checkbox-group">
                    <label
                      v-for="dia in diasSemanaOpcoes"
                      :key="dia.value"
                      class="checkbox-pill"
                      :class="{ 'checkbox-pill--active': form.dias_semana.includes(dia.value) }"
                    >
                      <input
                        type="checkbox"
                        :value="dia.value"
                        v-model="form.dias_semana"
                        style="display:none"
                      />
                      {{ dia.label }}
                    </label>
                  </div>
                </div>

                <!-- Dias do mês (mensal) -->
                <div v-if="form.frequencia === 'mensal'" class="field field-full">
                  <label>Dias do mês *</label>
                  <div class="dias-mes-grid">
                    <label
                      v-for="dia in diasMesOpcoes"
                      :key="dia.value"
                      class="checkbox-pill"
                      :class="{ 'checkbox-pill--active': form.dias_mes.includes(dia.value) }"
                    >
                      <input
                        type="checkbox"
                        :value="dia.value"
                        v-model="form.dias_mes"
                        style="display:none"
                      />
                      {{ dia.label }}
                    </label>
                  </div>
                </div>

                <!-- Data início -->
                <div class="field">
                  <label>Data início *</label>
                  <input v-model="form.data_inicio" type="date" class="input" />
                </div>

                <!-- Data fim -->
                <div class="field">
                  <label>Data fim</label>
                  <input v-model="form.data_fim" type="date" class="input" />
                </div>

                <!-- Descrição -->
                <div class="field field-full">
                  <label>Descrição</label>
                  <textarea v-model="form.descricao" class="input textarea" rows="2" placeholder="Opcional" />
                </div>

                <!-- Toggles -->
                <div class="field field-full toggles-row">
                  <label class="toggle-label">
                    <span>Foto obrigatória</span>
                    <div class="toggle" :class="{ 'toggle--on': form.foto_obrigatoria }" @click="form.foto_obrigatoria = !form.foto_obrigatoria">
                      <div class="toggle-thumb" />
                    </div>
                  </label>
                  <label class="toggle-label" v-if="form.foto_obrigatoria">
                    <span>Somente câmera (RN-03)</span>
                    <div class="toggle" :class="{ 'toggle--on': form.so_camera }" @click="form.so_camera = !form.so_camera">
                      <div class="toggle-thumb" />
                    </div>
                  </label>
                  <label class="toggle-label">
                    <span>Justificativa obrigatória</span>
                    <div class="toggle" :class="{ 'toggle--on': form.justif_obrigatoria }" @click="form.justif_obrigatoria = !form.justif_obrigatoria">
                      <div class="toggle-thumb" />
                    </div>
                  </label>
                </div>

                <!-- Status (edição) -->
                <div v-if="editandoId" class="field">
                  <label>Status</label>
                  <select v-model="form.status" class="input">
                    <option value="ativa">Ativa</option>
                    <option value="inativa">Inativa</option>
                  </select>
                </div>

                <!-- Colaboradores específicos -->
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

              <!-- Preview próximas datas -->
              <div v-if="editandoId" class="preview-box">
                <p class="preview-titulo">
                  <i class="pi pi-calendar" /> Próximas gerações
                  <span v-if="previewCarregando" class="pi pi-spin pi-spinner preview-spin" />
                </p>
                <div v-if="preview.length" class="preview-lista">
                  <span v-for="p in preview" :key="p.data" class="preview-item">
                    {{ p.data }} <span class="preview-dia">{{ p.dia_semana }}</span>
                  </span>
                </div>
                <p v-else class="preview-vazio">Salve a rotina para visualizar</p>
              </div>
            </div>
            <div class="dialog-footer">
              <button class="btn-ghost" @click="dialogAberto = false">Cancelar</button>
              <button class="btn-primary" :disabled="salvando" @click="salvarRotina">
                <i v-if="salvando" class="pi pi-spin pi-spinner" />
                {{ salvando ? 'Salvando...' : 'Salvar' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Confirm exclusão -->
    <AppConfirmDialog
      v-model:visivel="confirmAberto"
      titulo="Excluir rotina"
      variante="danger"
      label-confirmar="Excluir"
      :carregando="removendo"
      @confirmar="executarExcluir"
    >
      Deseja desativar e excluir a rotina
      <strong>{{ rotinaParaExcluir?.titulo }}</strong>?
      Execuções passadas serão preservadas.
    </AppConfirmDialog>
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1.25rem; }

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.page-title { font-size: 1.375rem; font-weight: 700; color: var(--color-text); margin: 0; }

/* Filtros */
.filtros { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.input-busca, .select-filtro {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  color: var(--color-text);
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
}
.input-busca { min-width: 200px; flex: 1; }
.input-busca:focus, .select-filtro:focus { border-color: var(--color-gold); }

/* Tabela */
.table-wrapper { overflow-x: auto; border-radius: 10px; border: 1px solid var(--color-border); }
.data-table { width: 100%; border-collapse: collapse; background: var(--color-surface); }
.data-table th {
  background: var(--color-surface-2);
  color: var(--color-text-muted);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0.75rem 1rem;
  text-align: left;
}
.data-table td { padding: 0.875rem 1rem; border-top: 1px solid var(--color-border); color: var(--color-text); font-size: 0.875rem; }
.data-row:hover { background: rgba(201,168,76,0.04); }
.td-titulo { font-weight: 500; }
.td-muted { color: var(--color-text-muted); }
.td-small { font-size: 0.8rem; }
.empty-row { text-align: center; color: var(--color-text-muted); padding: 2rem; }

/* Badges */
.badge {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.25rem 0.6rem; border-radius: 99px;
  font-size: 0.75rem; font-weight: 600;
}
.badge-ativa { background: rgba(34,197,94,0.15); color: #22C55E; }
.badge-inativa { background: rgba(107,114,128,0.15); color: #9CA3AF; }
.badge-foto { background: rgba(201,168,76,0.15); color: var(--color-gold); }
.badge-none { color: var(--color-text-muted); }

/* Botões */
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: var(--color-gold); color: #111; font-weight: 600;
  padding: 0.55rem 1.1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.875rem;
}
.btn-primary:hover { background: var(--color-gold-light); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-ghost {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: transparent; color: var(--color-text-muted); font-size: 0.875rem;
  padding: 0.5rem 0.875rem; border: 1px solid var(--color-border); border-radius: 8px; cursor: pointer;
}
.btn-ghost:hover { color: var(--color-text); border-color: var(--color-text-muted); }
.btn-ghost--danger:hover { color: #EF4444; border-color: #EF4444; }
.btn-icon {
  background: none; border: none; cursor: pointer; padding: 0.4rem;
  color: var(--color-text-muted); border-radius: 6px; font-size: 0.875rem;
}
.btn-icon:hover { color: var(--color-gold); background: rgba(201,168,76,0.1); }
.btn-icon--danger:hover { color: #EF4444; background: rgba(239,68,68,0.1); }
.td-actions { display: flex; gap: 0.25rem; }

/* Loading */
.loading-state { color: var(--color-text-muted); padding: 2rem; text-align: center; }

/* Cards mobile */
.cards-lista { display: flex; flex-direction: column; gap: 0.75rem; }
.rotina-card {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 10px; padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;
}
.card-top { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
.card-titulo { font-weight: 600; color: var(--color-text); }
.card-info { font-size: 0.8rem; color: var(--color-text-muted); display: flex; gap: 0.4rem; flex-wrap: wrap; }
.sep { opacity: 0.4; }
.card-actions { display: flex; gap: 0.5rem; }

/* Dialog */
.overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.7);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 1rem;
}
.dialog {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; width: 100%; max-width: 520px;
  max-height: 90vh; display: flex; flex-direction: column;
}
.dialog-lg { max-width: 680px; }
.dialog-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border);
}
.dialog-titulo { margin: 0; font-size: 1.125rem; font-weight: 600; color: var(--color-text); }
.btn-close {
  background: none; border: none; cursor: pointer; color: var(--color-text-muted);
  font-size: 1rem; padding: 0.25rem;
}
.btn-close:hover { color: var(--color-text); }
.dialog-body { overflow-y: auto; padding: 1.5rem; flex: 1; display: flex; flex-direction: column; gap: 1rem; }
.dialog-footer {
  display: flex; justify-content: flex-end; gap: 0.75rem;
  padding: 1rem 1.5rem; border-top: 1px solid var(--color-border);
}

/* Form */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.375rem; }
.field-full { grid-column: 1 / -1; }
.field label { font-size: 0.8rem; color: var(--color-text-muted); font-weight: 500; }
.label-hint { font-weight: 400; font-style: italic; }
.input {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text); padding: 0.5rem 0.75rem; border-radius: 8px;
  font-size: 0.875rem; outline: none; width: 100%;
}
.input:focus { border-color: var(--color-gold); }
.textarea { resize: vertical; min-height: 60px; font-family: inherit; }

/* Checkbox pills */
.checkbox-group { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.dias-mes-grid { display: flex; flex-wrap: wrap; gap: 0.375rem; }
.checkbox-pill {
  padding: 0.3rem 0.65rem; border-radius: 99px; font-size: 0.8rem; cursor: pointer;
  border: 1px solid var(--color-border); color: var(--color-text-muted);
  user-select: none; transition: all 0.15s;
}
.checkbox-pill--active { border-color: var(--color-gold); color: var(--color-gold); background: rgba(201,168,76,0.12); }

/* Toggles */
.toggles-row { display: flex; flex-direction: column; gap: 0.75rem; }
.toggle-label { display: flex; align-items: center; justify-content: space-between; }
.toggle-label > span { font-size: 0.875rem; color: var(--color-text); }
.toggle {
  width: 42px; height: 24px; background: var(--color-border);
  border-radius: 99px; cursor: pointer; position: relative;
  transition: background 0.2s; flex-shrink: 0;
}
.toggle--on { background: var(--color-gold); }
.toggle-thumb {
  position: absolute; top: 3px; left: 3px;
  width: 18px; height: 18px; background: white; border-radius: 50%;
  transition: transform 0.2s;
}
.toggle--on .toggle-thumb { transform: translateX(18px); }

/* Preview */
.preview-box {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  border-radius: 8px; padding: 0.875rem;
}
.preview-titulo { margin: 0 0 0.5rem; font-size: 0.8rem; color: var(--color-text-muted); font-weight: 600; display: flex; align-items: center; gap: 0.4rem; }
.preview-spin { margin-left: 0.5rem; }
.preview-lista { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.preview-item {
  background: rgba(201,168,76,0.1); border: 1px solid rgba(201,168,76,0.3);
  color: var(--color-text); font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 6px;
}
.preview-dia { color: var(--color-text-muted); }
.preview-vazio { font-size: 0.8rem; color: var(--color-text-muted); margin: 0; }

/* Transitions */
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.2s; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }
.overlay-enter-active .dialog { transition: transform 0.2s; }
.overlay-enter-from .dialog { transform: scale(0.96) translateY(-8px); }

.empty-state { text-align: center; color: var(--color-text-muted); padding: 2rem; }

@media (max-width: 640px) {
  .form-grid { grid-template-columns: 1fr; }
  .field-full { grid-column: 1; }
}
</style>
