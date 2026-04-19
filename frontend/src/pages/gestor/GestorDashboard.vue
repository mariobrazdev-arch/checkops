<script setup>
import { ref, computed, onMounted } from 'vue'
import { useDashboard } from '../../composables/useDashboard.js'
import { dashboardService } from '../../services/dashboard.service.js'
import { useToast } from '../../composables/useToast.js'
import Chart from 'primevue/chart'
import ProgressBar from 'primevue/progressbar'

const toast = useToast()
const { dados, carregando, erro, filtroAtivo, buscar, iniciarPolling } = useDashboard('gestor')

// ─── US-21: Alertas ──────────────────────────────────────────────────────────
const alertas          = ref([])
const carregandoAlerta = ref(false)

async function buscarAlertas() {
  carregandoAlerta.value = true
  try {
    const res = await dashboardService.alertas()
    alertas.value = res.data.data ?? []
  } catch { /* silencia */ }
  finally { carregandoAlerta.value = false }
}

async function marcarCiente(alerta) {
  try {
    await dashboardService.marcarCiente(alerta.id)
    alertas.value = alertas.value.filter((a) => a.id !== alerta.id)
    toast.sucesso('Alerta registrado como ciente por 7 dias')
  } catch {
    toast.erro('Erro ao marcar alerta')
  }
}

const alertasAtivos = computed(() => alertas.value.filter((a) => !a.silenciado))

// ─── Filtros de período ───────────────────────────────────────────────────────
const opcoesPeriodo = [
  { label: 'Hoje',        value: 'hoje'   },
  { label: 'Esta semana', value: 'semana' },
  { label: 'Este mês',    value: 'mes'    },
]

async function mudarPeriodo(valor) {
  filtroAtivo.value = valor
  await buscar()
}

// ─── Gráfico de conformidade ──────────────────────────────────────────────────
const chartData = computed(() => {
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

const chartOptions = {
  indexAxis: 'y',
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { min: 0, max: 100, ticks: { color: '#888', callback: (v) => v + '%' }, grid: { color: '#333' } },
    y: { ticks: { color: '#F0F0F0' }, grid: { display: false } },
  },
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

onMounted(async () => {
  await buscar()
  await buscarAlertas()
  iniciarPolling()
})
</script>

<template>
  <div class="page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Indicadores do seu setor</p>
      </div>
      <button class="btn-refresh" @click="buscar()" :disabled="carregando" title="Atualizar">
        <i :class="['pi', carregando ? 'pi-spin pi-spinner' : 'pi-refresh']" />
      </button>
    </div>

    <!-- Badge alertas -->
    <div v-if="alertasAtivos.length" class="alertas-banner">
      <i class="pi pi-exclamation-triangle" />
      <span><strong>{{ alertasAtivos.length }}</strong> alerta(s) de falha recorrente</span>
    </div>

    <!-- Filtro período -->
    <div class="periodo-bar">
      <button
        v-for="op in opcoesPeriodo" :key="op.value"
        :class="['btn-periodo', { ativo: filtroAtivo === op.value }]"
        @click="mudarPeriodo(op.value)"
      >{{ op.label }}</button>
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

      <!-- Gráfico conformidade -->
      <div class="bloco" v-if="(dados.conformidade_colaboradores ?? []).length">
        <h2 class="bloco-titulo">Conformidade por colaborador</h2>
        <div class="chart-wrap">
          <Chart type="bar" :data="chartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Listas inferiores -->
      <div class="listas-grid">
        <div class="bloco">
          <h2 class="bloco-titulo">Top rotinas negligenciadas</h2>
          <div v-if="!(dados.rotinas_criticas?.length)" class="lista-vazia">Sem dados no período</div>
          <ul v-else class="lista-simples">
            <li v-for="item in dados.rotinas_criticas" :key="item.rotina?.id" class="lista-item">
              <span class="lista-item__nome">{{ item.rotina?.titulo ?? '—' }}</span>
              <span class="lista-item__badge badge-danger">{{ item.falhas }} falha(s)</span>
            </li>
          </ul>
        </div>
        <div class="bloco">
          <h2 class="bloco-titulo">Justificativas mais frequentes</h2>
          <div v-if="!(dados.justificativas_frequentes?.length)" class="lista-vazia">Sem dados no período</div>
          <ul v-else class="lista-simples">
            <li v-for="item in dados.justificativas_frequentes" :key="item.texto" class="lista-item">
              <span class="lista-item__nome lista-item__nome--truncate">{{ item.texto }}</span>
              <span class="lista-item__badge badge-muted">{{ item.count }}×</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- US-21: Alertas de falha recorrente -->
      <div v-if="alertasAtivos.length" class="bloco bloco--alerta">
        <h2 class="bloco-titulo bloco-titulo--danger">
          <i class="pi pi-exclamation-circle" /> Falhas recorrentes
        </h2>
        <div v-if="carregandoAlerta" class="loading-central"><i class="pi pi-spin pi-spinner" /></div>
        <div v-else class="alertas-lista">
          <div v-for="alerta in alertasAtivos" :key="alerta.id" class="alerta-card">
            <div class="alerta-card__info">
              <strong>{{ alerta.colaborador?.nome }}</strong>
              <span class="alerta-card__sub">{{ alerta.rotina?.titulo }}</span>
              <span class="alerta-card__falhas">{{ alerta.falhas_consecutivas }} falhas consecutivas</span>
            </div>
            <button class="btn-ciente" @click="marcarCiente(alerta)">Ciente</button>
          </div>
        </div>
      </div>
    </template>
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
.alertas-banner {
  background: rgba(239,68,68,.15); border: 1px solid var(--status-atrasada);
  border-radius: 8px; padding: .75rem 1rem; color: var(--status-atrasada);
  display: flex; align-items: center; gap: .5rem; font-size: .875rem;
}
.periodo-bar { display: flex; gap: .5rem; flex-wrap: wrap; }
.btn-periodo {
  padding: .375rem .875rem; border-radius: 20px; border: 1px solid var(--color-border);
  background: var(--color-surface-2); color: var(--color-text-muted); font-size: .875rem;
  cursor: pointer; transition: all .2s;
}
.btn-periodo.ativo { background: var(--color-gold); border-color: var(--color-gold); color: #111; font-weight: 600; }
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
.bloco--alerta { border-color: rgba(239,68,68,.4); }
.bloco-titulo  { font-size: 1rem; font-weight: 600; color: var(--color-text); margin: 0 0 1rem; }
.bloco-titulo--danger { color: var(--status-atrasada); }
.chart-wrap { height: 220px; }
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
.alertas-lista { display: flex; flex-direction: column; gap: .75rem; }
.alerta-card {
  background: var(--color-surface-2); border: 1px solid rgba(239,68,68,.3);
  border-radius: 8px; padding: .875rem 1rem;
  display: flex; justify-content: space-between; align-items: center; gap: 1rem;
}
.alerta-card__info { display: flex; flex-direction: column; gap: .2rem; }
.alerta-card__sub   { font-size: .8rem; color: var(--color-text-muted); }
.alerta-card__falhas { font-size: .8rem; color: var(--status-atrasada); }
.btn-ciente {
  background: transparent; border: 1px solid var(--color-gold); border-radius: 8px;
  color: var(--color-gold); padding: .375rem .75rem; font-size: .8rem;
  cursor: pointer; white-space: nowrap; transition: background .2s;
}
.btn-ciente:hover { background: var(--color-gold); color: #111; }
.erro-msg { color: var(--status-atrasada); font-size: .875rem; }
.loading-central { display: flex; justify-content: center; padding: 2rem; color: var(--color-text-muted); }
</style>
