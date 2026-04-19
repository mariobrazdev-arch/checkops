<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { relatoriosService } from '../../services/relatorios.service.js'
import { useSetores } from '../../composables/useSetores.js'
import { useToast } from '../../composables/useToast.js'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import ProgressBar from 'primevue/progressbar'
import Button from 'primevue/button'
import MultiSelect from 'primevue/multiselect'

const toast = useToast()
const { setores, buscarSetores } = useSetores()

// ─── Filtros ──────────────────────────────────────────────────────────────────
const filtros = reactive({
  setor_id:       null,
  colaborador:    '',
  rotina:         '',
  status:         [],
  data_inicio:    null,
  data_fim:       null,
})

const opcoesStatus = [
  { label: 'Pendente',       value: 'pendente'       },
  { label: 'Realizada',      value: 'realizada'      },
  { label: 'Atrasada',       value: 'atrasada'       },
  { label: 'Não realizada',  value: 'nao_realizada'  },
]

// ─── Pré-visualização ─────────────────────────────────────────────────────────
const registros  = ref([])
const meta       = ref({})
const carregando = ref(false)

function buildParams(extra = {}) {
  return {
    setor_id:    filtros.setor_id    || undefined,
    colaborador: filtros.colaborador || undefined,
    rotina:      filtros.rotina      || undefined,
    status:      filtros.status.length ? filtros.status.join(',') : undefined,
    data_inicio: filtros.data_inicio ? filtros.data_inicio.toISOString().slice(0, 10) : undefined,
    data_fim:    filtros.data_fim    ? filtros.data_fim.toISOString().slice(0, 10)    : undefined,
    per_page:    20,
    ...extra,
  }
}

async function buscar(pg = 1) {
  carregando.value = true
  try {
    const res = await relatoriosService.listar(buildParams({ page: pg }))
    registros.value = res.data.data
    meta.value      = res.data.meta ?? {}
  } catch {
    toast.erro('Erro ao buscar relatório')
  } finally {
    carregando.value = false
  }
}

const totalRegistros = computed(() => meta.value.total ?? 0)

// ─── Exportação CSV com polling ───────────────────────────────────────────────
const exportandoCsv = ref(false)
const linkDownload  = ref(null)
let   pollingTimer  = null

async function exportar() {
  exportandoCsv.value = true
  linkDownload.value  = null

  try {
    const res = await relatoriosService.exportar(buildParams())
    const jobId = res.data.job_id
    iniciarPolling(jobId)
  } catch {
    exportandoCsv.value = false
    toast.erro('Erro ao iniciar exportação')
  }
}

function iniciarPolling(jobId) {
  pararPolling()
  pollingTimer = setInterval(async () => {
    try {
      const res = await relatoriosService.status(jobId)
      if (res.data.status === 'done') {
        pararPolling()
        exportandoCsv.value = false
        linkDownload.value  = res.data.url
        toast.sucesso('CSV pronto para download')
      }
    } catch {
      pararPolling()
      exportandoCsv.value = false
    }
  }, 3000)
  // Primeira verificação após 2s
  setTimeout(async () => {
    if (!pollingTimer) return
    try {
      const res = await relatoriosService.status(jobId)
      if (res.data.status === 'done') {
        pararPolling()
        exportandoCsv.value = false
        linkDownload.value  = res.data.url
        toast.sucesso('CSV pronto para download')
      }
    } catch { /* silencia */ }
  }, 2000)
}

function pararPolling() {
  if (pollingTimer) { clearInterval(pollingTimer); pollingTimer = null }
}

function formatarData(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR')
}

const BADGES = {
  realizada:     { label: 'Realizada',     bg: 'rgba(34,197,94,.15)',  cor: 'var(--status-realizada)' },
  pendente:      { label: 'Pendente',      bg: 'rgba(245,158,11,.15)', cor: 'var(--status-pendente)'  },
  atrasada:      { label: 'Atrasada',      bg: 'rgba(239,68,68,.15)',  cor: 'var(--status-atrasada)'  },
  nao_realizada: { label: 'Não realizada', bg: 'rgba(107,114,128,.15)',cor: 'var(--status-nao-realizada)' },
}

onMounted(async () => {
  await buscarSetores()
  await buscar()
})
onUnmounted(() => pararPolling())
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Relatórios</h1>
        <p class="page-subtitle">Exportar e visualizar execuções de rotinas</p>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-grid">
      <Select
        v-model="filtros.setor_id"
        :options="[{ id: null, nome: 'Todos os setores' }, ...setores]"
        optionLabel="nome" optionValue="id"
        placeholder="Setor" class="filtro-input"
      />
      <InputText v-model="filtros.colaborador" placeholder="Colaborador" class="filtro-input" @keyup.enter="buscar()" />
      <InputText v-model="filtros.rotina"      placeholder="Rotina"      class="filtro-input" @keyup.enter="buscar()" />
      <MultiSelect
        v-model="filtros.status"
        :options="opcoesStatus"
        optionLabel="label" optionValue="value"
        placeholder="Status" display="chip"
        class="filtro-input"
      />
      <DatePicker v-model="filtros.data_inicio" placeholder="De"  dateFormat="dd/mm/yy" class="filtro-input" showButtonBar />
      <DatePicker v-model="filtros.data_fim"    placeholder="Até" dateFormat="dd/mm/yy" class="filtro-input" showButtonBar />
      <div class="filtros-acoes">
        <Button label="Buscar" icon="pi pi-search" @click="buscar()" :loading="carregando" severity="secondary" size="small" />
        <Button label="Exportar CSV" icon="pi pi-download" @click="exportar()" :loading="exportandoCsv" size="small" />
      </div>
    </div>

    <!-- Status exportação -->
    <div v-if="exportandoCsv" class="export-status">
      <i class="pi pi-spin pi-spinner" /> Gerando CSV, aguarde...
      <ProgressBar mode="indeterminate" style="height:.25rem;margin-top:.5rem" />
    </div>

    <!-- Link download -->
    <div v-if="linkDownload" class="download-box">
      <i class="pi pi-check-circle" style="color:var(--status-realizada)" />
      <a :href="linkDownload" target="_blank" class="link-download">Baixar CSV de relatório</a>
    </div>

    <!-- Pré-visualização -->
    <div class="bloco">
      <div class="bloco-header">
        <h2 class="bloco-titulo">Pré-visualização</h2>
        <span class="total-badge" v-if="totalRegistros">{{ totalRegistros.toLocaleString('pt-BR') }} registros</span>
      </div>

      <div v-if="carregando && !registros.length" class="loading-central">
        <i class="pi pi-spin pi-spinner" style="font-size:1.5rem" />
      </div>

      <div v-else-if="!registros.length" class="lista-vazia">Nenhum registro encontrado</div>

      <div v-else class="table-wrap">
        <table class="tabela">
          <thead>
            <tr>
              <th>Data</th>
              <th>Rotina</th>
              <th>Setor</th>
              <th>Colaborador</th>
              <th>Status</th>
              <th>Previsto</th>
              <th>Respondido em</th>
              <th>Justificativa</th>
              <th>Foto</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in registros" :key="r.id">
              <td class="muted">{{ r.data }}</td>
              <td>{{ r.rotina?.titulo ?? '—' }}</td>
              <td class="muted">{{ r.rotina?.setor?.nome ?? '—' }}</td>
              <td>{{ r.colaborador?.nome ?? '—' }}</td>
              <td>
                <span
                  class="badge-status"
                  :style="{ background: BADGES[r.status]?.bg, color: BADGES[r.status]?.cor }"
                >{{ BADGES[r.status]?.label ?? r.status }}</span>
              </td>
              <td class="muted">{{ r.horario_previsto ?? '—' }}</td>
              <td class="muted">{{ formatarData(r.data_hora_resposta) }}</td>
              <td class="just">{{ r.justificativa ?? '—' }}</td>
              <td class="center">
                <a v-if="r.foto_url" :href="r.foto_url" target="_blank" title="Ver foto">
                  <i class="pi pi-image" style="color:var(--color-gold)" />
                </a>
                <span v-else class="muted">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <div v-if="meta.last_page > 1" class="paginacao">
        <button :disabled="meta.current_page <= 1" class="btn-pg" @click="buscar(meta.current_page - 1)">
          <i class="pi pi-chevron-left" />
        </button>
        <span class="pg-info">{{ meta.current_page }} / {{ meta.last_page }}</span>
        <button :disabled="meta.current_page >= meta.last_page" class="btn-pg" @click="buscar(meta.current_page + 1)">
          <i class="pi pi-chevron-right" />
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1.25rem; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; }
.page-title   { font-size: 1.5rem; font-weight: 700; color: var(--color-text); margin: 0; }
.page-subtitle { font-size: .875rem; color: var(--color-text-muted); margin: 0; }

.filtros-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr 150px 150px auto;
  gap: .75rem; align-items: end;
}
@media (max-width: 1100px) { .filtros-grid { grid-template-columns: 1fr 1fr 1fr; } }
@media (max-width: 640px)  { .filtros-grid { grid-template-columns: 1fr; } }
.filtro-input { width: 100%; }
.filtros-acoes { display: flex; gap: .5rem; align-items: center; }

.export-status {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 8px; padding: .875rem 1rem; color: var(--color-text-muted); font-size: .875rem;
}
.download-box {
  background: rgba(34,197,94,.1); border: 1px solid var(--status-realizada);
  border-radius: 8px; padding: .75rem 1rem; display: flex; align-items: center; gap: .75rem;
}
.link-download { color: var(--status-realizada); font-weight: 500; text-decoration: none; }
.link-download:hover { text-decoration: underline; }

.bloco { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; padding: 1.25rem; }
.bloco-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.bloco-titulo { font-size: 1rem; font-weight: 600; color: var(--color-text); margin: 0; }
.total-badge { background: var(--color-gold); color: #111; border-radius: 12px; padding: .2rem .75rem; font-size: .8rem; font-weight: 600; }

.table-wrap { overflow-x: auto; }
.tabela { width: 100%; border-collapse: collapse; font-size: .8rem; white-space: nowrap; }
.tabela th { text-align: left; color: var(--color-text-muted); font-weight: 500; padding: .5rem .75rem; border-bottom: 1px solid var(--color-border); }
.tabela td { padding: .55rem .75rem; color: var(--color-text); border-bottom: 1px solid rgba(51,51,51,.5); }
.tabela tr:last-child td { border-bottom: none; }
.muted  { color: var(--color-text-muted); }
.center { text-align: center; }
.just   { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.badge-status { border-radius: 12px; padding: .2rem .55rem; font-size: .7rem; font-weight: 500; }

.paginacao { display: flex; justify-content: center; align-items: center; gap: .75rem; padding-top: 1rem; }
.btn-pg {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  border-radius: 6px; width: 32px; height: 32px; color: var(--color-text);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.btn-pg:disabled { opacity: .4; cursor: not-allowed; }
.pg-info { font-size: .875rem; color: var(--color-text-muted); }
.loading-central { display: flex; justify-content: center; padding: 2rem; color: var(--color-text-muted); }
.lista-vazia { text-align: center; padding: 2rem; color: var(--color-text-muted); }
</style>
