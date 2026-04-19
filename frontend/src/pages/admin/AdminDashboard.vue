<script setup>
import { ref, computed, onMounted } from 'vue'
import { useDashboard } from '../../composables/useDashboard.js'
import { useSetores } from '../../composables/useSetores.js'
import { useToast } from '../../composables/useToast.js'
import Chart from 'primevue/chart'
import ProgressBar from 'primevue/progressbar'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'

const toast = useToast()
const { dados, carregando, erro, filtroAtivo, buscar, iniciarPolling } = useDashboard('admin')
const { setores, buscar: buscarSetores } = useSetores()

const setorFiltro = ref(null)
const setorDetalhe = ref(null)
const modalSetorAberto = ref(false)

const opcoesPeriodo = [
  { label: 'Hoje',        value: 'hoje'   },
  { label: 'Esta semana', value: 'semana' },
  { label: 'Este mês',    value: 'mes'    },
]

async function mudarPeriodo(valor) {
  filtroAtivo.value = valor
  await buscar({ setor_id: setorFiltro.value })
}

async function mudarSetor() {
  await buscar({ setor_id: setorFiltro.value })
}

// ─── Cards resumo ─────────────────────────────────────────────────────────────
const resumo = computed(() => dados.value?.resumo ?? {})

function pct(n) {
  const t = resumo.value.total ?? 0
  return t ? Math.round(((n ?? 0) / t) * 100) : 0
}

const cards = computed(() => [
  { label: 'Total',          val: resumo.value.total          ?? 0, pct: 100,                              cor: 'var(--color-text-muted)'     },
  { label: 'Concluídas',     val: resumo.value.concluidas     ?? 0, pct: pct(resumo.value.concluidas),     cor: 'var(--status-realizada)'     },
  { label: 'Pendentes',      val: resumo.value.pendentes      ?? 0, pct: pct(resumo.value.pendentes),      cor: 'var(--status-pendente)'      },
  { label: 'Atrasadas',      val: resumo.value.atrasadas      ?? 0, pct: pct(resumo.value.atrasadas),      cor: 'var(--status-atrasada)'      },
  { label: 'Não realizadas', val: resumo.value.nao_realizadas ?? 0, pct: pct(resumo.value.nao_realizadas), cor: 'var(--status-nao-realizada)'  },
])

// ─── Gráfico barras — conformidade colaboradores ──────────────────────────────
const chartBarData = computed(() => {
  const colabs = dados.value?.conformidade_colaboradores ?? []
  return {
    labels: colabs.map((c) => c.colaborador?.nome?.split(' ')[0] ?? '?'),
    datasets: [{
      label: 'Conformidade (%)',
      data: colabs.map((c) => Math.round(c.percentual)),
      backgroundColor: colabs.map((c) =>
        c.percentual >= 80 ? 'rgba(201,168,76,0.85)' : 'rgba(239,68,68,0.75)'
      ),
      borderRadius: 4,
      borderSkipped: false,
    }],
  }
})

const chartBarOptions = {
  indexAxis: 'y',
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { min: 0, max: 100, ticks: { color: '#888', callback: (v) => v + '%' }, grid: { color: '#333' } },
    y: { ticks: { color: '#F0F0F0' }, grid: { display: false } },
  },
}

// ─── Gráfico linha — histórico 30 dias ────────────────────────────────────────
const chartLinhaData = computed(() => {
  const hist = dados.value?.historico_diario ?? []
  return {
    labels: hist.map((h) => new Date(h.data).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })),
    datasets: [{
      label: 'Conformidade (%)',
      data: hist.map((h) => Math.round(h.percentual ?? 0)),
      borderColor: '#C9A84C',
      backgroundColor: 'rgba(201,168,76,0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 3,
      pointBackgroundColor: '#C9A84C',
    }],
  }
})

const chartLinhaOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: (ctx) => ` ${ctx.raw}%` } },
  },
  scales: {
    x: { ticks: { color: '#888', maxTicksLimit: 10 }, grid: { color: '#2a2a2a' } },
    y: { min: 0, max: 100, ticks: { color: '#888', callback: (v) => v + '%' }, grid: { color: '#2a2a2a' } },
  },
}

// ─── Ranking setores ──────────────────────────────────────────────────────────
function abrirSetorDetalhe(setor) {
  setorDetalhe.value = setor
  modalSetorAberto.value = true
}

function corPct(p) {
  if (p >= 80) return 'var(--status-realizada)'
  if (p >= 60) return 'var(--status-pendente)'
  return 'var(--status-atrasada)'
}

onMounted(async () => {
  await Promise.all([buscar(), buscarSetores()])
  iniciarPolling()
})
</script>

<template>
  <div class="page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Visão geral da empresa</p>
      </div>
      <button class="btn-refresh" @click="buscar({ setor_id: setorFiltro })" :disabled="carregando" title="Atualizar">
        <i :class="['pi', carregando ? 'pi-spin pi-spinner' : 'pi-refresh']" />
      </button>
    </div>

    <!-- Filtros -->
    <div class="filtros-bar">
      <div class="periodo-bar">
        <button
          v-for="op in opcoesPeriodo" :key="op.value"
          :class="['btn-periodo', { ativo: filtroAtivo === op.value }]"
          @click="mudarPeriodo(op.value)"
        >{{ op.label }}</button>
      </div>
      <Select
        v-model="setorFiltro"
        :options="[{ id: null, nome: 'Todos os setores' }, ...setores]"
        optionLabel="nome"
        optionValue="id"
        placeholder="Todos os setores"
        class="select-setor"
        @change="mudarSetor"
      />
    </div>

    <div v-if="erro" class="erro-msg">{{ erro }}</div>

    <div v-if="carregando && !dados" class="loading-central">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem" />
    </div>

    <template v-else-if="dados">
      <!-- Cards resumo -->
      <div class="cards-resumo">
        <div v-for="card in cards" :key="card.label" class="card-stat">
          <span class="card-stat__num" :style="{ color: card.cor }">{{ card.val }}</span>
          <span class="card-stat__pct">{{ card.pct }}%</span>
          <span class="card-stat__label">{{ card.label }}</span>
          <ProgressBar :value="card.pct" :pt="{ value: { style: { background: card.cor } } }" class="card-stat__bar" />
        </div>
      </div>

      <!-- Gráfico linha — histórico 30 dias -->
      <div class="bloco" v-if="(dados.historico_diario ?? []).length">
        <h2 class="bloco-titulo">Conformidade — últimos 30 dias</h2>
        <div class="chart-wrap chart-wrap--lg">
          <Chart type="line" :data="chartLinhaData" :options="chartLinhaOptions" />
        </div>
      </div>

      <!-- Ranking setores -->
      <div class="bloco" v-if="(dados.ranking_setores ?? []).length">
        <h2 class="bloco-titulo">Ranking de setores</h2>
        <div class="table-wrap">
          <table class="tabela-ranking">
            <thead>
              <tr>
                <th>Setor</th>
                <th>Gestor</th>
                <th>Total</th>
                <th>Concluídas</th>
                <th>Conformidade</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="setor in dados.ranking_setores" :key="setor.setor_id"
                class="tabela-ranking__linha"
                @click="abrirSetorDetalhe(setor)"
              >
                <td>{{ setor.setor_nome }}</td>
                <td class="muted">{{ setor.gestor_nome ?? '—' }}</td>
                <td class="num">{{ setor.total }}</td>
                <td class="num">{{ setor.concluidas }}</td>
                <td>
                  <div class="pct-inline">
                    <span :style="{ color: corPct(setor.percentual) }">{{ Math.round(setor.percentual) }}%</span>
                    <div class="pct-bar-bg">
                      <div class="pct-bar-fg" :style="{ width: setor.percentual + '%', background: corPct(setor.percentual) }" />
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Conformidade por colaborador -->
      <div class="bloco" v-if="(dados.conformidade_colaboradores ?? []).length">
        <h2 class="bloco-titulo">Conformidade por colaborador (Top 10)</h2>
        <div class="chart-wrap">
          <Chart type="bar" :data="chartBarData" :options="chartBarOptions" />
        </div>
      </div>

      <!-- Listas inferiores -->
      <div class="listas-grid">
        <div class="bloco">
          <h2 class="bloco-titulo">Top rotinas negligenciadas</h2>
          <div v-if="!(dados.rotinas_criticas?.length)" class="lista-vazia">Sem dados</div>
          <ul v-else class="lista-simples">
            <li v-for="item in dados.rotinas_criticas" :key="item.rotina?.id" class="lista-item">
              <span class="lista-item__nome">{{ item.rotina?.titulo ?? '—' }}</span>
              <span class="lista-item__badge badge-danger">{{ item.falhas }} falha(s)</span>
            </li>
          </ul>
        </div>
        <div class="bloco">
          <h2 class="bloco-titulo">Justificativas mais frequentes</h2>
          <div v-if="!(dados.justificativas_frequentes?.length)" class="lista-vazia">Sem dados</div>
          <ul v-else class="lista-simples">
            <li v-for="item in dados.justificativas_frequentes" :key="item.texto" class="lista-item">
              <span class="lista-item__nome lista-item__nome--truncate">{{ item.texto }}</span>
              <span class="lista-item__badge badge-muted">{{ item.count }}×</span>
            </li>
          </ul>
        </div>
      </div>
    </template>

    <!-- Modal detalhe setor -->
    <Dialog v-model:visible="modalSetorAberto" :header="setorDetalhe?.setor_nome" modal style="width:480px">
      <div v-if="setorDetalhe" class="modal-setor">
        <p><strong>Gestor:</strong> {{ setorDetalhe.gestor_nome ?? '—' }}</p>
        <p><strong>Total:</strong> {{ setorDetalhe.total }}</p>
        <p><strong>Concluídas:</strong> {{ setorDetalhe.concluidas }}</p>
        <p><strong>Conformidade:</strong>
          <span :style="{ color: corPct(setorDetalhe.percentual) }">
            {{ Math.round(setorDetalhe.percentual) }}%
          </span>
        </p>
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1.25rem; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; }
.page-title   { font-size: 1.5rem; font-weight: 700; color: var(--color-text); margin: 0; }
.page-subtitle { font-size: .875rem; color: var(--color-text-muted); margin: 0; }
.btn-refresh {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  border-radius: 8px; color: var(--color-text); width: 36px; height: 36px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.filtros-bar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; }
.periodo-bar { display: flex; gap: .5rem; flex-wrap: wrap; }
.btn-periodo {
  padding: .375rem .875rem; border-radius: 20px; border: 1px solid var(--color-border);
  background: var(--color-surface-2); color: var(--color-text-muted); font-size: .875rem;
  cursor: pointer; transition: all .2s;
}
.btn-periodo.ativo { background: var(--color-gold); border-color: var(--color-gold); color: #111; font-weight: 600; }
.select-setor { min-width: 200px; }
.cards-resumo { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: .75rem; }
.card-stat {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; padding: 1rem; display: flex; flex-direction: column; gap: .25rem;
}
.card-stat__num   { font-size: 1.75rem; font-weight: 700; line-height: 1; }
.card-stat__pct   { font-size: .875rem; color: var(--color-text-muted); }
.card-stat__label { font-size: .75rem; color: var(--color-text-muted); }
.card-stat__bar   { margin-top: .5rem; height: 4px; }
.bloco { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; padding: 1.25rem; }
.bloco-titulo { font-size: 1rem; font-weight: 600; color: var(--color-text); margin: 0 0 1rem; }
.chart-wrap { height: 220px; }
.chart-wrap--lg { height: 260px; }
.listas-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
@media (max-width: 640px) { .listas-grid { grid-template-columns: 1fr; } }
.lista-simples { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .5rem; }
.lista-item { display: flex; justify-content: space-between; align-items: center; gap: .5rem; }
.lista-item__nome { font-size: .875rem; color: var(--color-text); }
.lista-item__nome--truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 65%; }
.lista-item__badge { font-size: .75rem; border-radius: 12px; padding: .2rem .6rem; white-space: nowrap; }
.badge-danger { background: rgba(239,68,68,.2); color: var(--status-atrasada); }
.badge-muted  { background: var(--color-surface-2); color: var(--color-text-muted); }
.lista-vazia  { font-size: .875rem; color: var(--color-text-muted); }
.table-wrap { overflow-x: auto; }
.tabela-ranking { width: 100%; border-collapse: collapse; font-size: .875rem; }
.tabela-ranking th { text-align: left; color: var(--color-text-muted); font-weight: 500; padding: .5rem .75rem; border-bottom: 1px solid var(--color-border); }
.tabela-ranking td { padding: .6rem .75rem; color: var(--color-text); }
.tabela-ranking__linha { cursor: pointer; border-radius: 6px; transition: background .15s; }
.tabela-ranking__linha:hover { background: var(--color-surface-2); }
.muted { color: var(--color-text-muted); }
.num   { text-align: right; }
.pct-inline { display: flex; align-items: center; gap: .5rem; min-width: 120px; }
.pct-bar-bg { flex: 1; height: 6px; background: var(--color-border); border-radius: 3px; overflow: hidden; }
.pct-bar-fg { height: 100%; border-radius: 3px; transition: width .4s; }
.modal-setor { display: flex; flex-direction: column; gap: .75rem; color: var(--color-text); }
.erro-msg { color: var(--status-atrasada); font-size: .875rem; }
.loading-central { display: flex; justify-content: center; padding: 2rem; color: var(--color-text-muted); }
</style>
