<script setup>
import { ref, reactive, onMounted } from 'vue'
import { auditoriaService } from '../../services/auditoria.service.js'
import { useJobPolling } from '../../composables/useJobPolling.js'
import { useToast } from '../../composables/useToast.js'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import ProgressBar from 'primevue/progressbar'
import Button from 'primevue/button'

const toast = useToast()

// ─── Estado ───────────────────────────────────────────────────────────────────
const registros    = ref([])
const meta         = ref({})
const carregando   = ref(false)
const expandidos   = ref({})

const filtros = reactive({
  usuario:     '',
  acao:        null,
  entidade:    '',
  data_inicio: null,
  data_fim:    null,
  page:        1,
})

const opcoesAcao = [
  { label: 'Todas as ações', value: null },
  { label: 'login',                    value: 'login'                    },
  { label: 'logout',                   value: 'logout'                   },
  { label: 'criar',                    value: 'criar'                    },
  { label: 'atualizar',                value: 'atualizar'                },
  { label: 'deletar',                  value: 'deletar'                  },
  { label: 'reabrir_rotina',           value: 'reabrir_rotina'           },
  { label: 'alerta_falha_recorrente',  value: 'alerta_falha_recorrente'  },
  { label: 'sem_gps',                  value: 'sem_gps'                  },
  { label: 'alerta_foto_antiga',       value: 'alerta_foto_antiga'       },
]

// ─── Buscar ───────────────────────────────────────────────────────────────────
async function buscar(pg = 1) {
  carregando.value = true
  filtros.page = pg
  try {
    const params = {
      page:        filtros.page,
      usuario:     filtros.usuario   || undefined,
      acao:        filtros.acao      || undefined,
      entidade:    filtros.entidade  || undefined,
      data_inicio: filtros.data_inicio ? filtros.data_inicio.toISOString().slice(0, 10) : undefined,
      data_fim:    filtros.data_fim   ? filtros.data_fim.toISOString().slice(0, 10)   : undefined,
    }
    const res = await auditoriaService.listar(params)
    registros.value = res.data.data
    meta.value      = res.data.meta ?? {}
  } catch {
    toast.erro('Erro ao carregar auditoria')
  } finally {
    carregando.value = false
  }
}

// ─── Exportar CSV + polling ───────────────────────────────────────────────────
const exportandoCsv = ref(false)
const linkDownload  = ref(null)

const { jobStatus, pollingAtivo, iniciar: iniciarPolling } = useJobPolling(
  async () => {
    const jobId = linkDownload._jobId
    return await auditoriaService.status(jobId)
  },
  (url) => {
    linkDownload.value = url
    exportandoCsv.value = false
    toast.sucesso('CSV pronto para download')
  },
)

async function exportarCsv() {
  exportandoCsv.value = true
  linkDownload.value  = null
  try {
    const params = {
      usuario:     filtros.usuario   || undefined,
      acao:        filtros.acao      || undefined,
      entidade:    filtros.entidade  || undefined,
      data_inicio: filtros.data_inicio ? filtros.data_inicio.toISOString().slice(0, 10) : undefined,
      data_fim:    filtros.data_fim   ? filtros.data_fim.toISOString().slice(0, 10)   : undefined,
    }
    const res = await auditoriaService.exportar(params)
    const jobId = res.data.job_id
    linkDownload._jobId = jobId

    // Substituir fetchStatus do polling com o jobId real
    pollingJobId.value = jobId
    iniciarPollingComId(jobId)
  } catch {
    exportandoCsv.value = false
    toast.erro('Erro ao iniciar exportação')
  }
}

// Variante mais simples do polling diretamente nesta página
const pollingJobId = ref(null)
let pollingTimer   = null

function iniciarPollingComId(jobId) {
  pararPollingLocal()
  pollingTimer = setInterval(async () => {
    try {
      const res = await auditoriaService.status(jobId)
      if (res.data.status === 'done') {
        pararPollingLocal()
        exportandoCsv.value = false
        linkDownload.value  = res.data.url
        toast.sucesso('CSV pronto para download')
      }
    } catch {
      pararPollingLocal()
      exportandoCsv.value = false
    }
  }, 3000)
  // Primeira verificação após 2s
  setTimeout(async () => {
    if (!pollingTimer) return
    try {
      const res = await auditoriaService.status(jobId)
      if (res.data.status === 'done') {
        pararPollingLocal()
        exportandoCsv.value = false
        linkDownload.value  = res.data.url
        toast.sucesso('CSV pronto para download')
      }
    } catch { /* silencia */ }
  }, 2000)
}

function pararPollingLocal() {
  if (pollingTimer) { clearInterval(pollingTimer); pollingTimer = null }
}

function formatarData(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR')
}

function toggleExpandido(id) {
  expandidos.value[id] = !expandidos.value[id]
}

onMounted(() => buscar())
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Auditoria</h1>
        <p class="page-subtitle">Histórico de ações do sistema</p>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-grid">
      <InputText v-model="filtros.usuario"  placeholder="Usuário" class="filtro-input" @keyup.enter="buscar()" />
      <Select v-model="filtros.acao" :options="opcoesAcao" optionLabel="label" optionValue="value" placeholder="Ação" class="filtro-input" />
      <InputText v-model="filtros.entidade" placeholder="Entidade" class="filtro-input" @keyup.enter="buscar()" />
      <DatePicker v-model="filtros.data_inicio" placeholder="De" dateFormat="dd/mm/yy" class="filtro-input" showButtonBar />
      <DatePicker v-model="filtros.data_fim"    placeholder="Até" dateFormat="dd/mm/yy" class="filtro-input" showButtonBar />
      <div class="filtros-acoes">
        <Button label="Filtrar" icon="pi pi-search" @click="buscar()" :loading="carregando" severity="secondary" size="small" />
        <Button label="Exportar CSV" icon="pi pi-download" @click="exportarCsv()" :loading="exportandoCsv" size="small" />
      </div>
    </div>

    <!-- Progress exportação -->
    <div v-if="exportandoCsv" class="export-status">
      <i class="pi pi-spin pi-spinner" /> Gerando CSV, aguarde...
      <ProgressBar mode="indeterminate" style="height:.25rem;margin-top:.5rem" />
    </div>

    <!-- Link download -->
    <div v-if="linkDownload" class="download-box">
      <i class="pi pi-check-circle" style="color:var(--status-realizada)" />
      <a :href="linkDownload" target="_blank" class="link-download">Baixar CSV de auditoria</a>
    </div>

    <!-- Tabela -->
    <div class="bloco">
      <DataTable
        :value="registros"
        :loading="carregando"
        dataKey="id"
        v-model:expandedRows="expandidos"
        rowExpansionTemplate="expansao"
        size="small"
        stripedRows
        class="tabela-audit"
      >
        <template #empty><div class="lista-vazia">Nenhum registro encontrado</div></template>

        <Column expander style="width:3rem" />

        <Column header="Data/Hora" style="min-width:160px">
          <template #body="{ data }">{{ formatarData(data.created_at) }}</template>
        </Column>

        <Column header="Usuário" style="min-width:140px">
          <template #body="{ data }">{{ data.usuario?.nome ?? '—' }}</template>
        </Column>

        <Column field="acao" header="Ação" style="min-width:160px">
          <template #body="{ data }">
            <span class="badge-acao">{{ data.acao }}</span>
          </template>
        </Column>

        <Column field="entidade" header="Entidade" style="min-width:130px" />

        <Column header="IP" style="min-width:120px">
          <template #body="{ data }">{{ data.ip ?? '—' }}</template>
        </Column>
      </DataTable>

      <!-- Expansão — dados antes/depois -->
      <template v-for="row in registros" :key="'exp-' + row.id">
        <div v-if="expandidos[row.id]" class="expansao-row">
          <div class="expansao-cols">
            <div>
              <p class="expansao-label">Dados antes</p>
              <pre class="json-pre">{{ JSON.stringify(row.dados_antes, null, 2) ?? 'null' }}</pre>
            </div>
            <div>
              <p class="expansao-label">Dados depois</p>
              <pre class="json-pre">{{ JSON.stringify(row.dados_depois, null, 2) ?? 'null' }}</pre>
            </div>
          </div>
        </div>
      </template>

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
  grid-template-columns: 1fr 1fr 1fr 160px 160px auto;
  gap: .75rem;
  align-items: end;
  flex-wrap: wrap;
}
@media (max-width: 900px) { .filtros-grid { grid-template-columns: 1fr 1fr; } }
.filtro-input { width: 100%; }
.filtros-acoes { display: flex; gap: .5rem; align-items: center; }

.export-status {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 8px; padding: .875rem 1rem; color: var(--color-text-muted);
  font-size: .875rem;
}

.download-box {
  background: rgba(34,197,94,.1); border: 1px solid var(--status-realizada);
  border-radius: 8px; padding: .75rem 1rem;
  display: flex; align-items: center; gap: .75rem;
}
.link-download { color: var(--status-realizada); font-weight: 500; text-decoration: none; }
.link-download:hover { text-decoration: underline; }

.bloco { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; padding: 1.25rem; }

.badge-acao {
  background: var(--color-surface-2); border-radius: 12px;
  padding: .2rem .6rem; font-size: .75rem; color: var(--color-gold);
}

.expansao-row {
  background: var(--color-surface-2); border-top: 1px solid var(--color-border);
  padding: 1rem;
}
.expansao-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 640px) { .expansao-cols { grid-template-columns: 1fr; } }
.expansao-label { font-size: .75rem; color: var(--color-text-muted); margin: 0 0 .5rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.json-pre {
  background: #0d0d0d; border: 1px solid var(--color-border); border-radius: 6px;
  padding: .75rem; font-size: .75rem; color: var(--color-gold-light);
  overflow-x: auto; max-height: 200px; white-space: pre-wrap;
}

.paginacao { display: flex; justify-content: center; align-items: center; gap: .75rem; padding-top: 1rem; }
.btn-pg {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  border-radius: 6px; width: 32px; height: 32px; color: var(--color-text);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.btn-pg:disabled { opacity: .4; cursor: not-allowed; }
.pg-info { font-size: .875rem; color: var(--color-text-muted); }
.lista-vazia { text-align: center; padding: 2rem; color: var(--color-text-muted); }
</style>
