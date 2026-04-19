<script setup>
/**
 * ColaboradorHistorico.vue — US-15
 * Histórico de rotinas do colaborador autenticado.
 * Mobile-first: cards expansíveis com paginação "carregar mais".
 */
import { ref, computed, onMounted } from 'vue'
import Calendar from 'primevue/calendar'
import ProgressBar from 'primevue/progressbar'
import AppBadgeStatus from '../../components/ui/AppBadgeStatus.vue'
import FotoViewer from '../../components/shared/FotoViewer.vue'
import { useRotinas } from '../../composables/useRotinas.js'

const { historico, loading, carregandoMais, temMais, buscarHistorico, carregarMaisHistorico } = useRotinas()

// ─── Filtros ──────────────────────────────────────────────────────────────────
const periodo     = ref(null)  // [Date, Date] | null
const statusAtivo = ref([])   // array de strings selecionadas

const STATUS_OPCOES = [
  { label: 'Realizada',     value: 'realizada',     cor: 'var(--status-realizada)' },
  { label: 'Não realizada', value: 'nao_realizada',  cor: 'var(--status-nao-realizada)' },
  { label: 'Atrasada',      value: 'atrasada',       cor: 'var(--status-atrasada)' },
  { label: 'Pendente',      value: 'pendente',       cor: 'var(--status-pendente)' },
]

// ─── Cards expansíveis ────────────────────────────────────────────────────────
const expandidos = ref(new Set())
function toggleExpand(id) {
  if (expandidos.value.has(id)) expandidos.value.delete(id)
  else expandidos.value.add(id)
}

// ─── Busca ────────────────────────────────────────────────────────────────────
function filtrosAtivos() {
  const f = {}
  if (periodo.value?.[0]) f.data_inicio = fmtData(periodo.value[0])
  if (periodo.value?.[1]) f.data_fim    = fmtData(periodo.value[1])
  if (statusAtivo.value.length) f.status = statusAtivo.value.join(',')
  return f
}

function fmtData(d) { return d.toISOString().slice(0, 10) }

function aplicarFiltros() { buscarHistorico(filtrosAtivos(), 1) }
function carregarMais()   { carregarMaisHistorico(filtrosAtivos()) }

function toggleStatus(val) {
  const idx = statusAtivo.value.indexOf(val)
  if (idx === -1) statusAtivo.value.push(val)
  else statusAtivo.value.splice(idx, 1)
  aplicarFiltros()
}

function limpar() {
  periodo.value = null
  statusAtivo.value = []
  buscarHistorico({}, 1)
}

// ─── Formatação ───────────────────────────────────────────────────────────────
function fmtDataBr(str) {
  if (!str) return '—'
  const [y, m, d] = str.split('-')
  return `${d}/${m}/${y}`
}
function fmtDataHora(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

const temFiltroAtivo = computed(() => periodo.value || statusAtivo.value.length > 0)

onMounted(() => buscarHistorico({}, 1))
</script>

<template>
  <div class="page-historico">
    <header class="page-header">
      <h1 class="page-titulo">Meu Histórico</h1>
    </header>

    <!-- Filtro período -->
    <div class="filtros">
      <Calendar
        v-model="periodo"
        selectionMode="range"
        dateFormat="dd/mm/yy"
        placeholder="Filtrar por período"
        :showIcon="true"
        :numberOfMonths="1"
        @update:modelValue="aplicarFiltros"
        pt:root:style="width:100%"
      />

      <!-- Chips de status -->
      <div class="status-chips">
        <button
          v-for="op in STATUS_OPCOES"
          :key="op.value"
          class="chip"
          :class="{ 'chip--ativo': statusAtivo.includes(op.value) }"
          :style="statusAtivo.includes(op.value) ? `background:${op.cor}22; border-color:${op.cor}; color:${op.cor}` : ''"
          @click="toggleStatus(op.value)"
        >
          {{ op.label }}
        </button>
      </div>

      <button v-if="temFiltroAtivo" class="btn-limpar" @click="limpar">
        <i class="pi pi-times" /> Limpar filtros
      </button>
    </div>

    <!-- Loading inicial -->
    <ProgressBar v-if="loading && historico.length === 0" mode="indeterminate" style="height:3px;margin-bottom:1rem" />

    <!-- Estado vazio -->
    <div v-else-if="!loading && historico.length === 0" class="empty-state">
      <i class="pi pi-calendar-times" />
      <p>Nenhuma rotina encontrada neste período.</p>
    </div>

    <!-- Lista de cards -->
    <div v-else class="lista-historico">
      <div
        v-for="item in historico"
        :key="item.id"
        class="card-hist"
        :class="`card-hist--${item.status}`"
      >
        <!-- Cabeçalho do card (sempre visível) -->
        <div class="card-head" @click="toggleExpand(item.id)">
          <div class="card-info">
            <span class="card-titulo">{{ item.rotina?.titulo ?? '—' }}</span>
            <span class="card-data">{{ fmtDataBr(item.data) }}</span>
          </div>
          <div class="card-meta">
            <AppBadgeStatus :status="item.status" />
            <span class="card-hora">{{ fmtDataHora(item.data_hora_resposta) }}</span>
          </div>
          <i
            class="pi card-chevron"
            :class="expandidos.has(item.id) ? 'pi-chevron-up' : 'pi-chevron-down'"
          />
        </div>

        <!-- Expansão -->
        <Transition name="expand">
          <div v-if="expandidos.has(item.id)" class="card-body">
            <!-- Justificativa -->
            <div v-if="item.justificativa" class="justificativa">
              <span class="label-small">Justificativa</span>
              <p class="justificativa-texto">{{ item.justificativa }}</p>
            </div>

            <!-- Foto (miniatura) -->
            <div v-if="item.foto_url" class="foto-mini">
              <span class="label-small">Foto registrada</span>
              <FotoViewer
                :foto-url="item.foto_url"
                :foto-timestamp="item.foto_timestamp"
                :device-id="item.foto_device_id"
                :lat="item.foto_lat"
                :lng="item.foto_lng"
                :mapa-url="item.mapa_url"
              />
            </div>

            <p v-if="!item.justificativa && !item.foto_url" class="sem-detalhe">
              Sem detalhes adicionais.
            </p>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Carregar mais -->
    <div v-if="temMais && !carregandoMais && !loading" class="carregar-mais">
      <button class="btn-mais" @click="carregarMais">Carregar mais</button>
    </div>
    <div v-if="carregandoMais" class="loading-mais">
      <i class="pi pi-spin pi-spinner" /> Carregando…
    </div>
  </div>
</template>

<style scoped>
.page-historico { padding: 1.25rem; max-width: 680px; margin: 0 auto; }

.page-header { margin-bottom: 1.25rem; }
.page-titulo { font-size: 1.35rem; font-weight: 700; color: var(--color-text); margin: 0; }

/* Filtros */
.filtros { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.25rem; }

.status-chips { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.chip {
  padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.8rem; font-weight: 500; cursor: pointer;
  background: var(--color-surface-2); border: 1px solid var(--color-border); color: var(--color-text-muted);
  transition: all 0.15s;
}
.chip:hover { border-color: var(--color-text-muted); }

.btn-limpar {
  background: none; border: 1px solid var(--color-border); color: var(--color-text-muted);
  padding: 0.4rem 0.9rem; border-radius: 8px; cursor: pointer; font-size: 0.82rem;
  display: inline-flex; align-items: center; gap: 0.35rem; align-self: flex-start;
}
.btn-limpar:hover { border-color: var(--color-gold); color: var(--color-gold); }

/* Estado vazio */
.empty-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.75rem; padding: 4rem 2rem; color: var(--color-text-muted); text-align: center;
}
.empty-state .pi { font-size: 2.5rem; }

/* Cards */
.lista-historico { display: flex; flex-direction: column; gap: 0.65rem; }

.card-hist {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; overflow: hidden;
  border-left: 4px solid var(--color-border);
}
.card-hist--realizada     { border-left-color: var(--status-realizada); }
.card-hist--atrasada      { border-left-color: var(--status-atrasada); }
.card-hist--nao_realizada { border-left-color: var(--status-nao-realizada); }
.card-hist--pendente      { border-left-color: var(--status-pendente); }

.card-head {
  display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; cursor: pointer;
}
.card-head:hover { background: var(--color-surface-2); }

.card-info { flex: 1; min-width: 0; }
.card-titulo { display: block; font-weight: 600; color: var(--color-text); font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.card-data   { font-size: 0.75rem; color: var(--color-text-muted); }

.card-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 0.2rem; flex-shrink: 0; }
.card-hora  { font-size: 0.75rem; color: var(--color-text-muted); }

.card-chevron { color: var(--color-text-muted); font-size: 0.85rem; flex-shrink: 0; }

/* Expansão */
.card-body {
  border-top: 1px solid var(--color-border);
  padding: 0.9rem 1rem; display: flex; flex-direction: column; gap: 0.9rem;
  background: var(--color-surface-2);
}

.label-small { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--color-text-muted); display: block; margin-bottom: 0.4rem; }

.justificativa-texto {
  background: var(--color-surface); border-left: 3px solid var(--color-gold);
  padding: 0.6rem 0.85rem; border-radius: 0 8px 8px 0; font-size: 0.88rem;
  color: var(--color-text); margin: 0; line-height: 1.5;
}

.sem-detalhe { font-size: 0.83rem; color: var(--color-text-muted); margin: 0; }

/* Paginação */
.carregar-mais { display: flex; justify-content: center; margin-top: 1.25rem; }
.btn-mais {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text); padding: 0.6rem 1.75rem; border-radius: 10px; cursor: pointer; font-size: 0.9rem;
}
.btn-mais:hover { border-color: var(--color-gold); color: var(--color-gold); }

.loading-mais {
  display: flex; justify-content: center; align-items: center; gap: 0.5rem;
  color: var(--color-text-muted); margin-top: 1rem; font-size: 0.85rem;
}

/* Transição expand */
.expand-enter-active, .expand-leave-active { transition: opacity 0.2s ease; }
.expand-enter-from, .expand-leave-to { opacity: 0; }
</style>
