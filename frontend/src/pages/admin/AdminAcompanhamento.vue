<script setup>
import { ref, computed, onMounted } from 'vue'
import { relatoriosService } from '../../services/relatorios.service.js'
import { validacaoService } from '../../services/validacao.service.js'
import { useSetores } from '../../composables/useSetores.js'
import { useUiStore } from '../../stores/ui.store.js'
import RotinaDetalhe from '../../components/shared/RotinaDetalhe.vue'

const uiStore = useUiStore()
const { setores, buscar: buscarSetores } = useSetores()

const itens        = ref([])
const loading      = ref(false)
const selecionada  = ref(null)

const filtroData        = ref(hoje())
const filtroSetor       = ref('')
const filtroColaborador = ref('')
const filtroStatus      = ref('')

function hoje() {
  return new Date().toISOString().slice(0, 10)
}

function fmtDataHora(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

async function buscar() {
  loading.value = true
  try {
    const params = { per_page: 500, data_inicio: filtroData.value, data_fim: filtroData.value }
    if (filtroSetor.value)       params.setor_id    = filtroSetor.value
    if (filtroColaborador.value) params.colaborador  = filtroColaborador.value
    if (filtroStatus.value)      params.status       = filtroStatus.value
    const { data } = await relatoriosService.listar(params)
    itens.value = data.data ?? []
  } catch {
    uiStore.addToast({ severity: 'error', summary: 'Erro ao carregar acompanhamento' })
  } finally {
    loading.value = false
  }
}

async function abrirDetalhe(item) {
  if (item.status !== 'realizada') return
  try {
    const { data } = await validacaoService.detalhe(item.id)
    selecionada.value = data.data ?? data
  } catch {
    uiStore.addToast({ severity: 'error', summary: 'Erro ao abrir detalhe' })
  }
}

// Agrupa por setor → colaborador
const porSetor = computed(() => {
  const mapaSetores = new Map()
  for (const item of itens.value) {
    const setorId   = item.rotina?.setor?.id   ?? 'sem-setor'
    const setorNome = item.rotina?.setor?.nome  ?? 'Sem setor'
    const colabId   = item.colaborador_id       ?? 'desconhecido'
    const colabNome = item.colaborador?.nome    ?? 'Desconhecido'

    if (!mapaSetores.has(setorId)) mapaSetores.set(setorId, { nome: setorNome, colaboradores: new Map() })
    const setor = mapaSetores.get(setorId)
    if (!setor.colaboradores.has(colabId)) setor.colaboradores.set(colabId, { nome: colabNome, itens: [] })
    setor.colaboradores.get(colabId).itens.push(item)
  }
  return [...mapaSetores.values()]
    .map(s => ({
      nome: s.nome,
      colaboradores: [...s.colaboradores.values()].sort((a, b) => a.nome.localeCompare(b.nome)),
    }))
    .sort((a, b) => a.nome.localeCompare(b.nome))
})

const statusConfig = {
  pendente:      { label: 'Pendente',      cor: '#F59E0B', bg: 'rgba(245,158,11,0.12)' },
  atrasada:      { label: 'Atrasada',      cor: '#EF4444', bg: 'rgba(239,68,68,0.12)' },
  realizada:     { label: 'Realizada',     cor: '#22C55E', bg: 'rgba(34,197,94,0.12)' },
  nao_realizada: { label: 'Não realizada', cor: '#6B7280', bg: 'rgba(107,114,128,0.12)' },
}

function contagem(itensColab) {
  const c = { realizada: 0, pendente: 0, atrasada: 0, nao_realizada: 0 }
  for (const i of itensColab) c[i.status] = (c[i.status] ?? 0) + 1
  return c
}

let debounce = null
function onColabInput() {
  clearTimeout(debounce)
  debounce = setTimeout(() => buscar(), 400)
}

onMounted(async () => {
  await buscarSetores()
  buscar()
})
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Acompanhamento</h1>
        <p class="page-sub">Rotinas de todos os setores por colaborador</p>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filtros">
      <input v-model="filtroData" type="date" class="f-input" @change="buscar" />
      <select v-model="filtroSetor" class="f-input" @change="buscar">
        <option value="">Todos os setores</option>
        <option v-for="s in setores" :key="s.id" :value="s.id">{{ s.nome }}</option>
      </select>
      <select v-model="filtroStatus" class="f-input" @change="buscar">
        <option value="">Todos os status</option>
        <option value="pendente">Pendente</option>
        <option value="atrasada">Atrasada</option>
        <option value="realizada">Realizada</option>
        <option value="nao_realizada">Não realizada</option>
      </select>
      <input
        v-model="filtroColaborador"
        type="text"
        class="f-input"
        placeholder="Buscar colaborador…"
        @input="onColabInput"
      />
      <button class="btn-buscar" @click="buscar" :disabled="loading">
        <i class="pi pi-refresh" />
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="estado-centro">
      <i class="pi pi-spin pi-spinner" /> Carregando...
    </div>

    <!-- Vazio -->
    <div v-else-if="porSetor.length === 0" class="estado-vazio">
      <i class="pi pi-calendar-times" />
      <p>Nenhuma rotina encontrada para este dia.</p>
    </div>

    <!-- Conteúdo por setor -->
    <div v-else class="setores-lista">
      <div v-for="setor in porSetor" :key="setor.nome" class="setor-bloco">
        <!-- Cabeçalho do setor -->
        <div class="setor-header">
          <i class="pi pi-sitemap setor-icon" />
          <span class="setor-nome">{{ setor.nome }}</span>
        </div>

        <!-- Colaboradores do setor -->
        <div class="colaboradores">
          <div v-for="colab in setor.colaboradores" :key="colab.nome" class="colab-bloco">
            <div class="colab-header">
              <div class="colab-avatar">{{ colab.nome.charAt(0).toUpperCase() }}</div>
              <span class="colab-nome">{{ colab.nome }}</span>
              <div class="colab-resumo">
                <span
                  v-for="(qtd, st) in contagem(colab.itens)"
                  :key="st"
                  v-show="qtd > 0"
                  class="resumo-pill"
                  :style="{ color: statusConfig[st].cor, background: statusConfig[st].bg }"
                >{{ qtd }} {{ statusConfig[st].label.toLowerCase() }}</span>
              </div>
            </div>

            <div class="rotinas-lista">
              <div
                v-for="item in colab.itens"
                :key="item.id"
                class="rotina-row"
                :class="{ 'rotina-row--clicavel': item.status === 'realizada' }"
                @click="abrirDetalhe(item)"
              >
                <div class="rotina-info">
                  <span class="rotina-titulo">{{ item.rotina?.titulo ?? '—' }}</span>
                  <span class="rotina-horario">{{ item.rotina?.horario_previsto?.slice(0,5) ?? '' }}</span>
                </div>
                <div class="rotina-direita">
                  <span v-if="item.data_hora_resposta" class="rotina-hora-resp">
                    {{ fmtDataHora(item.data_hora_resposta) }}
                  </span>
                  <span
                    class="status-badge"
                    :style="{ color: statusConfig[item.status]?.cor, background: statusConfig[item.status]?.bg }"
                  >{{ statusConfig[item.status]?.label ?? item.status }}</span>
                  <span v-if="item.status === 'realizada' && (item.fotos?.length || item.foto_url)" class="foto-badge">
                    <i class="pi pi-image" />
                    <span v-if="(item.fotos?.length ?? 0) > 1">{{ item.fotos.length }}</span>
                  </span>
                  <i v-if="item.status === 'realizada'" class="pi pi-chevron-right chevron" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal detalhe -->
    <RotinaDetalhe
      :rotina="selecionada"
      :pode-reabrir="false"
      @fechar="selecionada = null"
    />
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1.25rem; }
.page-header { display: flex; align-items: flex-start; justify-content: space-between; }
.page-title { font-size: 1.375rem; font-weight: 700; color: var(--color-text); margin: 0; }
.page-sub { font-size: 0.8125rem; color: var(--color-text-muted); margin: 0.15rem 0 0; }

.filtros { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }
.f-input {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text); padding: 0.5rem 0.75rem; border-radius: 8px;
  font-size: 0.875rem; outline: none;
}
.f-input:focus { border-color: var(--color-gold); }
.btn-buscar {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text-muted); padding: 0.5rem 0.75rem; border-radius: 8px; cursor: pointer;
}
.btn-buscar:hover:not(:disabled) { border-color: var(--color-gold); color: var(--color-gold); }
.btn-buscar:disabled { opacity: .5; cursor: not-allowed; }

.estado-centro { color: var(--color-text-muted); padding: 2rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
.estado-vazio {
  display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
  padding: 4rem 2rem; color: var(--color-text-muted);
}
.estado-vazio .pi { font-size: 2.5rem; }

/* Setor */
.setores-lista { display: flex; flex-direction: column; gap: 1.5rem; }
.setor-bloco {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; overflow: hidden;
}
.setor-header {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 0.75rem 1rem; background: rgba(201,168,76,0.08);
  border-bottom: 1px solid var(--color-border);
}
.setor-icon { color: var(--color-gold); font-size: 0.95rem; }
.setor-nome { font-weight: 700; color: var(--color-gold); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; }

/* Colaboradores */
.colaboradores { display: flex; flex-direction: column; }
.colab-bloco { border-bottom: 1px solid var(--color-border); }
.colab-bloco:last-child { border-bottom: none; }

.colab-header {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.7rem 1rem; background: var(--color-surface-2);
  border-bottom: 1px solid var(--color-border);
}
.colab-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  background: rgba(201,168,76,0.15); color: var(--color-gold);
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 0.8rem; flex-shrink: 0;
}
.colab-nome { font-weight: 600; color: var(--color-text); font-size: 0.875rem; flex: 1; }
.colab-resumo { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.resumo-pill { font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 99px; }

/* Rotinas */
.rotinas-lista { display: flex; flex-direction: column; }
.rotina-row {
  display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
  padding: 0.625rem 1rem; border-bottom: 1px solid rgba(51,51,51,0.5);
}
.rotina-row:last-child { border-bottom: none; }
.rotina-row--clicavel { cursor: pointer; }
.rotina-row--clicavel:hover { background: rgba(201,168,76,0.04); }

.rotina-info { display: flex; flex-direction: column; gap: 0.1rem; flex: 1; min-width: 0; }
.rotina-titulo { font-size: 0.84rem; color: var(--color-text); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rotina-horario { font-size: 0.72rem; color: var(--color-text-muted); }

.rotina-direita { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.rotina-hora-resp { font-size: 0.72rem; color: var(--color-text-muted); }
.status-badge { font-size: 0.72rem; font-weight: 600; padding: 0.15rem 0.55rem; border-radius: 99px; white-space: nowrap; }
.foto-badge { display: inline-flex; align-items: center; gap: 0.2rem; color: var(--color-gold); font-size: 0.72rem; }
.chevron { font-size: 0.72rem; color: var(--color-border); }
</style>
