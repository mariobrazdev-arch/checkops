<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { relatoriosService } from '../../services/relatorios.service.js'
import { useSetores } from '../../composables/useSetores.js'
import { useToast } from '../../composables/useToast.js'
import FotoViewer from '../../components/shared/FotoViewer.vue'

const toast = useToast()
const { setores, buscar: buscarSetores } = useSetores()

// ─── Presets de período ───────────────────────────────────────────────────────
const presets = [
  { label: 'Hoje',       dias: 0  },
  { label: '7 dias',     dias: 7  },
  { label: '15 dias',    dias: 15 },
  { label: '30 dias',    dias: 30 },
  { label: '90 dias',    dias: 90 },
]
const presetAtivo = ref(7)

function aplicarPreset(dias) {
  presetAtivo.value = dias
  const hoje = new Date()
  const ini  = new Date()
  if (dias === 0) {
    ini.setHours(0, 0, 0, 0)
    filtros.data_inicio = hoje.toISOString().slice(0, 10)
    filtros.data_fim    = hoje.toISOString().slice(0, 10)
  } else {
    ini.setDate(ini.getDate() - dias)
    filtros.data_inicio = ini.toISOString().slice(0, 10)
    filtros.data_fim    = hoje.toISOString().slice(0, 10)
  }
}

// ─── Filtros ──────────────────────────────────────────────────────────────────
const filtros = reactive({
  setor_id:    '',
  colaborador: '',
  rotina:      '',
  status:      '',
  data_inicio: '',
  data_fim:    '',
})

const opcoesStatus = [
  { label: 'Todos',          value: '' },
  { label: 'Realizada',      value: 'realizada' },
  { label: 'Pendente',       value: 'pendente' },
  { label: 'Atrasada',       value: 'atrasada' },
  { label: 'Não realizada',  value: 'nao_realizada' },
]

function buildParams() {
  const p = {}
  if (filtros.setor_id)    p.setor_id    = filtros.setor_id
  if (filtros.colaborador) p.colaborador = filtros.colaborador
  if (filtros.rotina)      p.rotina      = filtros.rotina
  if (filtros.status)      p.status      = filtros.status
  if (filtros.data_inicio) p.data_inicio = filtros.data_inicio
  if (filtros.data_fim)    p.data_fim    = filtros.data_fim
  return p
}

// ─── KPIs ─────────────────────────────────────────────────────────────────────
const kpi = ref({ total: 0, realizadas: 0, naoRealizadas: 0, atrasadas: 0, pendentes: 0, conformidade: 0 })
const kpiCarregando = ref(false)

async function buscarResumo() {
  kpiCarregando.value = true
  try {
    const res = await relatoriosService.resumo(buildParams())
    kpi.value = res.data.data
  } catch {
    /* silencia — KPIs são auxiliares */
  } finally {
    kpiCarregando.value = false
  }
}

// ─── Tabela ───────────────────────────────────────────────────────────────────
const registros  = ref([])
const meta       = ref({})
const carregando = ref(false)

async function buscar(pg = 1) {
  carregando.value = true
  try {
    const res = await relatoriosService.listar({ ...buildParams(), page: pg, per_page: 50 })
    registros.value = res.data.data
    meta.value      = res.data.meta ?? {}
  } catch {
    toast.erro('Erro ao buscar registros')
  } finally {
    carregando.value = false
  }
}

async function aplicarFiltros() {
  presetAtivo.value = null
  await Promise.all([buscarResumo(), buscar(1)])
}

// ─── Exportação CSV ───────────────────────────────────────────────────────────
const exportando   = ref(false)
const linkDownload = ref(null)
let   pollingTimer = null

async function exportar() {
  exportando.value  = true
  linkDownload.value = null
  try {
    const res = await relatoriosService.exportar(buildParams())
    iniciarPolling(res.data.job_id)
  } catch {
    exportando.value = false
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
        exportando.value   = false
        linkDownload.value = res.data.url
        toast.sucesso('CSV pronto para download')
      }
    } catch { pararPolling(); exportando.value = false }
  }, 3000)
}

function pararPolling() {
  if (pollingTimer) { clearInterval(pollingTimer); pollingTimer = null }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
const STATUS = {
  realizada:     { label: 'Realizada',     cor: 'var(--status-realizada)',     bg: 'rgba(34,197,94,.12)'   },
  pendente:      { label: 'Pendente',      cor: 'var(--status-pendente)',      bg: 'rgba(245,158,11,.12)'  },
  atrasada:      { label: 'Atrasada',      cor: 'var(--status-atrasada)',      bg: 'rgba(239,68,68,.12)'   },
  nao_realizada: { label: 'Não realizada', cor: 'var(--status-nao-realizada)', bg: 'rgba(107,114,128,.12)' },
}

function fmtData(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' })
}
function fmtDia(iso) {
  if (!iso) return '—'
  return new Date(iso + 'T00:00:00').toLocaleDateString('pt-BR')
}

const totalPages = computed(() => meta.value.last_page ?? 1)
const curPage    = computed(() => meta.value.current_page ?? 1)

// ─── Visualizador de fotos ────────────────────────────────────────────────────
const registroFotos = ref(null)

function abrirFotos(r) {
  if (!r.foto_url && !r.fotos?.length) return
  registroFotos.value = r
}

onMounted(async () => {
  await buscarSetores()
  aplicarPreset(7)
  await Promise.all([buscarResumo(), buscar(1)])
})
onUnmounted(() => pararPolling())
</script>

<template>
  <div class="page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Relatórios</h1>
        <p class="page-sub">Histórico de execuções de rotinas</p>
      </div>
      <div class="header-acoes">
        <button class="btn-export" @click="exportar" :disabled="exportando">
          <i :class="exportando ? 'pi pi-spin pi-spinner' : 'pi pi-download'" />
          {{ exportando ? 'Gerando...' : 'Exportar CSV' }}
        </button>
      </div>
    </div>

    <!-- Link download -->
    <div v-if="linkDownload" class="download-banner">
      <i class="pi pi-check-circle" />
      <span>CSV gerado!</span>
      <a :href="linkDownload" target="_blank" class="link-dl">Clique para baixar</a>
    </div>

    <!-- Filtros -->
    <div class="filtros-card">
      <!-- Presets de período -->
      <div class="presets">
        <button
          v-for="p in presets" :key="p.dias"
          class="preset-btn"
          :class="{ ativo: presetAtivo === p.dias }"
          @click="aplicarPreset(p.dias)"
        >{{ p.label }}</button>
      </div>

      <div class="filtros-linha">
        <div class="filtro-grupo">
          <label>De</label>
          <input type="date" v-model="filtros.data_inicio" class="f-input" @change="presetAtivo = null" />
        </div>
        <div class="filtro-grupo">
          <label>Até</label>
          <input type="date" v-model="filtros.data_fim" class="f-input" @change="presetAtivo = null" />
        </div>
        <div class="filtro-grupo">
          <label>Setor</label>
          <select v-model="filtros.setor_id" class="f-input">
            <option value="">Todos</option>
            <option v-for="s in setores" :key="s.id" :value="s.id">{{ s.nome }}</option>
          </select>
        </div>
        <div class="filtro-grupo">
          <label>Status</label>
          <select v-model="filtros.status" class="f-input">
            <option v-for="op in opcoesStatus" :key="op.value" :value="op.value">{{ op.label }}</option>
          </select>
        </div>
        <div class="filtro-grupo">
          <label>Colaborador</label>
          <input type="text" v-model="filtros.colaborador" placeholder="Nome..." class="f-input" @keyup.enter="aplicarFiltros" />
        </div>
        <div class="filtro-grupo">
          <label>Rotina</label>
          <input type="text" v-model="filtros.rotina" placeholder="Título..." class="f-input" @keyup.enter="aplicarFiltros" />
        </div>
        <button class="btn-buscar" @click="aplicarFiltros" :disabled="carregando">
          <i class="pi pi-search" /> Buscar
        </button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="kpis">
      <div class="kpi-card">
        <span class="kpi-label">Total</span>
        <span class="kpi-valor">{{ kpi.total.toLocaleString('pt-BR') }}</span>
        <span class="kpi-sub">execuções</span>
      </div>
      <div class="kpi-card kpi--gold">
        <span class="kpi-label">Conformidade</span>
        <span class="kpi-valor">{{ kpi.conformidade }}%</span>
        <div class="kpi-barra-bg">
          <div class="kpi-barra-fg" :style="{ width: kpi.conformidade + '%' }" />
        </div>
      </div>
      <div class="kpi-card kpi--verde">
        <span class="kpi-label">Realizadas</span>
        <span class="kpi-valor">{{ kpi.realizadas.toLocaleString('pt-BR') }}</span>
        <span class="kpi-sub">{{ kpi.total > 0 ? Math.round(kpi.realizadas/kpi.total*100) : 0 }}% do total</span>
      </div>
      <div class="kpi-card kpi--vermelho">
        <span class="kpi-label">Não realizadas</span>
        <span class="kpi-valor">{{ kpi.naoRealizadas.toLocaleString('pt-BR') }}</span>
        <span class="kpi-sub">{{ kpi.total > 0 ? Math.round(kpi.naoRealizadas/kpi.total*100) : 0 }}% do total</span>
      </div>
      <div class="kpi-card kpi--ambar">
        <span class="kpi-label">Atrasadas</span>
        <span class="kpi-valor">{{ kpi.atrasadas.toLocaleString('pt-BR') }}</span>
        <span class="kpi-sub">{{ kpi.total > 0 ? Math.round(kpi.atrasadas/kpi.total*100) : 0 }}% do total</span>
      </div>
    </div>

    <!-- Tabela -->
    <div class="tabela-card">
      <div class="tabela-header">
        <h2 class="tabela-titulo">Execuções</h2>
        <span v-if="meta.total" class="total-tag">
          {{ meta.total.toLocaleString('pt-BR') }} registros
        </span>
      </div>

      <div v-if="carregando && !registros.length" class="estado-centro">
        <i class="pi pi-spin pi-spinner" /> Carregando...
      </div>
      <div v-else-if="!registros.length" class="estado-centro">
        <i class="pi pi-inbox" style="font-size:1.5rem" />
        <p>Nenhum registro encontrado</p>
      </div>

      <div v-else class="table-scroll">
        <table class="tabela">
          <thead>
            <tr>
              <th>Data</th>
              <th>Rotina</th>
              <th>Setor</th>
              <th>Colaborador</th>
              <th>Previsto</th>
              <th>Status</th>
              <th>Respondido em</th>
              <th>Justificativa</th>
              <th>Localização</th>
              <th>Fotos</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in registros" :key="r.id">
              <td class="muted nowrap">{{ fmtDia(r.data) }}</td>
              <td class="fw500">{{ r.rotina?.titulo ?? '—' }}</td>
              <td class="muted">{{ r.rotina?.setor?.nome ?? '—' }}</td>
              <td>{{ r.colaborador?.nome ?? '—' }}</td>
              <td class="muted nowrap">{{ r.rotina?.horario_previsto ?? '—' }}</td>
              <td>
                <span
                  class="badge"
                  :style="{ background: STATUS[r.status]?.bg, color: STATUS[r.status]?.cor }"
                >{{ STATUS[r.status]?.label ?? r.status }}</span>
              </td>
              <td class="muted nowrap">{{ fmtData(r.data_hora_resposta) }}</td>
              <td class="just">
                <span :title="r.justificativa || ''">{{ r.justificativa ?? '—' }}</span>
              </td>
              <td class="center">
                <a v-if="r.mapa_url" :href="r.mapa_url" target="_blank" class="link-mapa" title="Ver no mapa">
                  <i class="pi pi-map-marker" />
                </a>
                <span v-else class="muted">—</span>
              </td>
              <td class="center">
                <button
                  v-if="r.foto_url || r.fotos?.length"
                  class="link-foto btn-icon"
                  title="Ver fotos"
                  @click="abrirFotos(r)"
                >
                  <i class="pi pi-image" />
                  <span v-if="(r.fotos?.length ?? 0) > 1" class="foto-count">{{ r.fotos.length }}</span>
                </button>
                <span v-else class="muted">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <div v-if="totalPages > 1" class="paginacao">
        <button class="pg-btn" :disabled="curPage <= 1" @click="buscar(curPage - 1)">
          <i class="pi pi-chevron-left" />
        </button>
        <div class="pg-numeros">
          <button
            v-for="n in totalPages" :key="n"
            v-show="Math.abs(n - curPage) <= 2 || n === 1 || n === totalPages"
            class="pg-num"
            :class="{ ativo: n === curPage }"
            @click="buscar(n)"
          >{{ n }}</button>
        </div>
        <button class="pg-btn" :disabled="curPage >= totalPages" @click="buscar(curPage + 1)">
          <i class="pi pi-chevron-right" />
        </button>
        <span class="pg-info">{{ curPage }} / {{ totalPages }}</span>
      </div>
    </div>

    <!-- Modal de fotos -->
    <Teleport to="body">
      <Transition name="overlay">
        <div v-if="registroFotos" class="overlay" @click.self="registroFotos = null">
          <div class="fotos-dialog">
            <div class="fotos-dialog-header">
              <span class="fotos-dialog-titulo">{{ registroFotos.rotina?.titulo }}</span>
              <button class="btn-close" @click="registroFotos = null"><i class="pi pi-times" /></button>
            </div>
            <div class="fotos-dialog-body">
              <FotoViewer
                :fotos="registroFotos.fotos?.length ? registroFotos.fotos : null"
                :foto-url="registroFotos.foto_url"
                :foto-timestamp="registroFotos.foto_timestamp"
                :device-id="registroFotos.foto_device_id"
                :lat="registroFotos.foto_lat"
                :lng="registroFotos.foto_lng"
                :mapa-url="registroFotos.mapa_url"
              />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1.25rem; }

/* Header */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.page-title  { font-size: 1.5rem; font-weight: 700; color: var(--color-text); margin: 0; }
.page-sub    { font-size: .875rem; color: var(--color-text-muted); margin: .2rem 0 0; }
.header-acoes { display: flex; gap: .5rem; align-items: center; }

.btn-export {
  display: inline-flex; align-items: center; gap: .4rem;
  background: var(--color-gold); color: #111; font-weight: 600;
  border: none; border-radius: 8px; padding: .55rem 1.1rem;
  font-size: .875rem; cursor: pointer;
}
.btn-export:hover:not(:disabled) { background: var(--color-gold-light); }
.btn-export:disabled { opacity: .6; cursor: not-allowed; }

/* Download banner */
.download-banner {
  display: flex; align-items: center; gap: .75rem;
  background: rgba(34,197,94,.1); border: 1px solid var(--status-realizada);
  border-radius: 8px; padding: .75rem 1rem;
  font-size: .875rem; color: var(--status-realizada);
}
.link-dl { color: var(--status-realizada); font-weight: 600; text-decoration: underline; }

/* Filtros */
.filtros-card {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; padding: 1.125rem 1.25rem; display: flex; flex-direction: column; gap: .875rem;
}
.presets { display: flex; gap: .5rem; flex-wrap: wrap; }
.preset-btn {
  padding: .3rem .75rem; border-radius: 99px; font-size: .8rem; cursor: pointer;
  border: 1px solid var(--color-border); background: var(--color-surface-2);
  color: var(--color-text-muted); transition: all .15s;
}
.preset-btn.ativo {
  border-color: var(--color-gold); background: rgba(201,168,76,.12); color: var(--color-gold); font-weight: 600;
}
.filtros-linha {
  display: flex; gap: .75rem; flex-wrap: wrap; align-items: flex-end;
}
.filtro-grupo { display: flex; flex-direction: column; gap: .25rem; }
.filtro-grupo label { font-size: .75rem; color: var(--color-text-muted); font-weight: 500; }
.f-input {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text); padding: .45rem .75rem; border-radius: 8px;
  font-size: .875rem; outline: none; min-width: 130px;
}
.f-input:focus { border-color: var(--color-gold); }
.btn-buscar {
  display: inline-flex; align-items: center; gap: .4rem;
  background: var(--color-surface-2); color: var(--color-text);
  border: 1px solid var(--color-border); border-radius: 8px;
  padding: .45rem 1rem; font-size: .875rem; cursor: pointer;
  align-self: flex-end;
}
.btn-buscar:hover:not(:disabled) { border-color: var(--color-gold); color: var(--color-gold); }
.btn-buscar:disabled { opacity: .5; cursor: not-allowed; }

/* KPIs */
.kpis { display: grid; grid-template-columns: repeat(5, 1fr); gap: .875rem; }
@media (max-width: 1100px) { .kpis { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px)  { .kpis { grid-template-columns: 1fr 1fr; } }

.kpi-card {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; padding: 1rem 1.125rem;
  display: flex; flex-direction: column; gap: .25rem;
}
.kpi-label { font-size: .75rem; color: var(--color-text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: .04em; }
.kpi-valor { font-size: 1.625rem; font-weight: 700; color: var(--color-text); line-height: 1.1; }
.kpi-sub   { font-size: .75rem; color: var(--color-text-muted); }

.kpi--gold   { border-color: rgba(201,168,76,.3); }
.kpi--gold .kpi-valor { color: var(--color-gold); }
.kpi--verde .kpi-valor  { color: var(--status-realizada); }
.kpi--vermelho .kpi-valor { color: var(--status-atrasada); }
.kpi--ambar .kpi-valor  { color: var(--status-pendente); }

.kpi-barra-bg { height: 4px; background: var(--color-surface-2); border-radius: 99px; overflow: hidden; margin-top: .375rem; }
.kpi-barra-fg { height: 100%; background: var(--color-gold); border-radius: 99px; transition: width .5s ease; }

/* Tabela */
.tabela-card {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; overflow: hidden;
}
.tabela-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.25rem; border-bottom: 1px solid var(--color-border);
}
.tabela-titulo { font-size: 1rem; font-weight: 600; color: var(--color-text); margin: 0; }
.total-tag {
  background: rgba(201,168,76,.15); color: var(--color-gold);
  border-radius: 99px; padding: .2rem .7rem; font-size: .75rem; font-weight: 600;
}

.table-scroll { overflow-x: auto; }
.tabela { width: 100%; border-collapse: collapse; font-size: .8125rem; }
.tabela th {
  background: var(--color-surface-2); text-align: left;
  color: var(--color-text-muted); font-size: .7rem; font-weight: 600;
  text-transform: uppercase; letter-spacing: .04em;
  padding: .625rem 1rem; white-space: nowrap;
}
.tabela td { padding: .7rem 1rem; color: var(--color-text); border-bottom: 1px solid var(--color-border); vertical-align: middle; }
.tabela tr:last-child td { border-bottom: none; }
.tabela tr:hover td { background: rgba(201,168,76,.03); }

.muted  { color: var(--color-text-muted); }
.nowrap { white-space: nowrap; }
.fw500  { font-weight: 500; }
.center { text-align: center; }
.just   { max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--color-text-muted); font-size: .775rem; }

.badge { border-radius: 99px; padding: .2rem .6rem; font-size: .7rem; font-weight: 600; white-space: nowrap; }

.link-mapa { color: #60A5FA; font-size: 1rem; }
.link-mapa:hover { color: #93C5FD; }
.link-foto { color: var(--color-gold); font-size: 1rem; position: relative; display: inline-flex; align-items: center; gap: .2rem; }
.link-foto:hover { color: var(--color-gold-light); }
.btn-icon { background: none; border: none; cursor: pointer; padding: 0; }
.foto-count {
  background: var(--color-gold); color: #111; border-radius: 99px;
  font-size: .6rem; font-weight: 700; padding: 0 .35rem; line-height: 1.4;
}

/* Paginação */
.paginacao {
  display: flex; align-items: center; justify-content: center;
  gap: .5rem; padding: 1rem; border-top: 1px solid var(--color-border);
}
.pg-btn {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  border-radius: 6px; width: 30px; height: 30px; color: var(--color-text);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.pg-btn:disabled { opacity: .35; cursor: not-allowed; }
.pg-numeros { display: flex; gap: .25rem; }
.pg-num {
  background: none; border: 1px solid transparent; border-radius: 6px;
  width: 30px; height: 30px; font-size: .8rem; color: var(--color-text-muted);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.pg-num:hover { background: var(--color-surface-2); }
.pg-num.ativo { background: var(--color-gold); color: #111; font-weight: 700; border-color: var(--color-gold); }
.pg-info { font-size: .8rem; color: var(--color-text-muted); margin-left: .25rem; }

/* Modal fotos */
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,.75); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem; }
.fotos-dialog {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; width: 100%; max-width: 520px; display: flex; flex-direction: column;
}
.fotos-dialog-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.25rem; border-bottom: 1px solid var(--color-border);
}
.fotos-dialog-titulo { font-size: .95rem; font-weight: 600; color: var(--color-text); }
.btn-close { background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 1rem; }
.fotos-dialog-body { padding: 1.25rem; overflow-y: auto; max-height: 80vh; }
.overlay-enter-active, .overlay-leave-active { transition: opacity .2s; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }

/* Estado vazio/loading */
.estado-centro {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: .75rem; padding: 3rem; color: var(--color-text-muted); font-size: .9rem;
}
.estado-centro p { margin: 0; }
</style>
