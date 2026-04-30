<script setup>
import { ref, computed, onMounted } from 'vue'
import { validacaoService } from '../../services/validacao.service.js'
import { usuariosService } from '../../services/usuarios.service.js'
import { useUiStore } from '../../stores/ui.store.js'
import FotoViewer from '../../components/shared/FotoViewer.vue'

const uiStore = useUiStore()

const itens          = ref([])
const colaboradores  = ref([])
const loading        = ref(false)
const selecionada    = ref(null)

const filtroColaborador = ref('')
const filtroStatus      = ref('')
const filtroData        = ref(hoje())

function hoje() {
  return new Date().toISOString().slice(0, 10)
}

function fmtDataHora(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

// ─── Busca ────────────────────────────────────────────────────────────────────
async function buscar() {
  loading.value = true
  try {
    const params = { per_page: 200 }
    if (filtroData.value)        { params.data_inicio = filtroData.value; params.data_fim = filtroData.value }
    if (filtroStatus.value)      params.status = filtroStatus.value
    if (filtroColaborador.value) params.colaborador_id = filtroColaborador.value

    const { data } = await validacaoService.listar(params)
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

// ─── Agrupado por colaborador ─────────────────────────────────────────────────
const porColaborador = computed(() => {
  const mapa = new Map()
  for (const item of itens.value) {
    const id   = item.colaborador?.id ?? 'desconhecido'
    const nome = item.colaborador?.nome ?? 'Desconhecido'
    if (!mapa.has(id)) mapa.set(id, { nome, itens: [] })
    mapa.get(id).itens.push(item)
  }
  return [...mapa.values()].sort((a, b) => a.nome.localeCompare(b.nome))
})

const statusConfig = {
  pendente:      { label: 'Pendente',       cor: '#F59E0B', bg: 'rgba(245,158,11,0.12)' },
  atrasada:      { label: 'Atrasada',       cor: '#EF4444', bg: 'rgba(239,68,68,0.12)' },
  realizada:     { label: 'Realizada',      cor: '#22C55E', bg: 'rgba(34,197,94,0.12)' },
  nao_realizada: { label: 'Não realizada',  cor: '#6B7280', bg: 'rgba(107,114,128,0.12)' },
}

function contagem(itensColab) {
  const c = { realizada: 0, pendente: 0, atrasada: 0, nao_realizada: 0 }
  for (const i of itensColab) c[i.status] = (c[i.status] ?? 0) + 1
  return c
}

onMounted(async () => {
  buscar()
  try {
    const { data } = await usuariosService.listarColaboradores({ per_page: 100 })
    colaboradores.value = data.data ?? []
  } catch { /* silencioso */ }
})
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Acompanhamento</h1>
        <p class="page-sub">Rotinas do setor por colaborador</p>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filtros">
      <input v-model="filtroData" type="date" class="filtro-input" @change="buscar" />
      <select v-model="filtroColaborador" class="filtro-input" @change="buscar">
        <option value="">Todos os colaboradores</option>
        <option v-for="c in colaboradores" :key="c.id" :value="c.id">{{ c.nome }}</option>
      </select>
      <select v-model="filtroStatus" class="filtro-input" @change="buscar">
        <option value="">Todos os status</option>
        <option value="pendente">Pendente</option>
        <option value="atrasada">Atrasada</option>
        <option value="realizada">Realizada</option>
        <option value="nao_realizada">Não realizada</option>
      </select>
      <button class="btn-buscar" @click="buscar">
        <i class="pi pi-refresh" />
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state"><i class="pi pi-spin pi-spinner" /> Carregando...</div>

    <!-- Vazio -->
    <div v-else-if="porColaborador.length === 0" class="empty-state">
      <i class="pi pi-calendar-times" />
      <p>Nenhuma rotina encontrada para este dia.</p>
      <span class="empty-hint">Verifique se o comando <code>rotinas:gerar</code> foi executado.</span>
    </div>

    <!-- Por colaborador -->
    <div v-else class="colaboradores-lista">
      <div v-for="grupo in porColaborador" :key="grupo.nome" class="colab-bloco">
        <!-- Cabeçalho do colaborador -->
        <div class="colab-header">
          <div class="colab-avatar">{{ grupo.nome.charAt(0).toUpperCase() }}</div>
          <span class="colab-nome">{{ grupo.nome }}</span>
          <div class="colab-resumo">
            <span
              v-for="(qtd, st) in contagem(grupo.itens)"
              :key="st"
              v-show="qtd > 0"
              class="resumo-pill"
              :style="{ color: statusConfig[st].cor, background: statusConfig[st].bg }"
            >
              {{ qtd }} {{ statusConfig[st].label.toLowerCase() }}
            </span>
          </div>
        </div>

        <!-- Rotinas do colaborador -->
        <div class="rotinas-lista">
          <div
            v-for="item in grupo.itens"
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
              >
                {{ statusConfig[item.status]?.label ?? item.status }}
              </span>
              <i v-if="item.status === 'realizada'" class="pi pi-chevron-right chevron" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal detalhe simplificado -->
    <Teleport to="body">
      <Transition name="overlay">
        <div v-if="selecionada" class="overlay" @click.self="selecionada = null">
          <div class="detalhe-dialog">
            <div class="detalhe-header">
              <h3>{{ selecionada.rotina?.titulo }}</h3>
              <button class="btn-close" @click="selecionada = null"><i class="pi pi-times" /></button>
            </div>
            <div class="detalhe-body">
              <div class="detalhe-meta">
                <span><i class="pi pi-user" /> {{ selecionada.colaborador?.nome }}</span>
                <span><i class="pi pi-clock" /> {{ fmtDataHora(selecionada.data_hora_resposta) }}</span>
                <span v-if="selecionada.foto_lat"><i class="pi pi-map-marker" /> GPS registrado</span>
              </div>
              <FotoViewer
                :fotos="selecionada.fotos?.length ? selecionada.fotos : null"
                :foto-url="selecionada.foto_url"
                :lat="selecionada.foto_lat"
                :lng="selecionada.foto_lng"
                :mapa-url="selecionada.mapa_url"
              />
              <div v-if="selecionada.justificativa" class="justif-box">
                <p class="justif-label">Justificativa</p>
                <p class="justif-texto">{{ selecionada.justificativa }}</p>
              </div>
              <div v-if="!selecionada.foto_url && !selecionada.fotos?.length && !selecionada.justificativa" class="sem-evidencia">
                <i class="pi pi-check-circle" /> Rotina realizada sem foto obrigatória
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1.25rem; }
.page-header { display: flex; align-items: flex-start; justify-content: space-between; }
.page-title { font-size: 1.375rem; font-weight: 700; color: var(--color-text); margin: 0; }
.page-sub { font-size: 0.8125rem; color: var(--color-text-muted); margin: 0.15rem 0 0; }

.filtros { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }
.filtro-input {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text); padding: 0.5rem 0.75rem; border-radius: 8px;
  font-size: 0.875rem; outline: none;
}
.filtro-input:focus { border-color: var(--color-gold); }
.btn-buscar {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text-muted); padding: 0.5rem 0.75rem; border-radius: 8px; cursor: pointer;
}
.btn-buscar:hover { border-color: var(--color-gold); color: var(--color-gold); }

.loading-state { color: var(--color-text-muted); padding: 2rem; text-align: center; }

.empty-state {
  display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
  padding: 4rem 2rem; color: var(--color-text-muted);
}
.empty-state .pi { font-size: 2.5rem; }
.empty-hint { font-size: 0.8rem; }
.empty-hint code { background: var(--color-surface-2); padding: 0.1rem 0.4rem; border-radius: 4px; }

.colaboradores-lista { display: flex; flex-direction: column; gap: 1rem; }

.colab-bloco {
  background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; overflow: hidden;
}

.colab-header {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.875rem 1rem; background: var(--color-surface-2); border-bottom: 1px solid var(--color-border);
}
.colab-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: rgba(201,168,76,0.15); color: var(--color-gold);
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 0.875rem; flex-shrink: 0;
}
.colab-nome { font-weight: 600; color: var(--color-text); font-size: 0.9rem; flex: 1; }
.colab-resumo { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.resumo-pill { font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.55rem; border-radius: 99px; }

.rotinas-lista { display: flex; flex-direction: column; }

.rotina-row {
  display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
  padding: 0.75rem 1rem; border-bottom: 1px solid var(--color-border);
}
.rotina-row:last-child { border-bottom: none; }
.rotina-row--clicavel { cursor: pointer; }
.rotina-row--clicavel:hover { background: rgba(201,168,76,0.04); }

.rotina-info { display: flex; flex-direction: column; gap: 0.1rem; flex: 1; min-width: 0; }
.rotina-titulo { font-size: 0.875rem; color: var(--color-text); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rotina-horario { font-size: 0.75rem; color: var(--color-text-muted); }

.rotina-direita { display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0; }
.rotina-hora-resp { font-size: 0.75rem; color: var(--color-text-muted); }
.status-badge { font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 99px; white-space: nowrap; }
.chevron { font-size: 0.75rem; color: var(--color-border); }

/* Modal detalhe */
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.detalhe-dialog {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; width: 100%; max-width: 480px; max-height: 90vh; display: flex; flex-direction: column;
}
.detalhe-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border);
}
.detalhe-header h3 { margin: 0; font-size: 1.05rem; color: var(--color-text); font-weight: 600; }
.btn-close { background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 1rem; }
.detalhe-body { overflow-y: auto; padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.detalhe-meta { display: flex; flex-direction: column; gap: 0.4rem; }
.detalhe-meta span { font-size: 0.85rem; color: var(--color-text-muted); display: flex; align-items: center; gap: 0.4rem; }
.foto-wrap { border-radius: 10px; overflow: hidden; }
.foto-full { width: 100%; display: block; border-radius: 10px; }
.justif-box { background: var(--color-surface-2); border-radius: 8px; padding: 0.875rem; }
.justif-label { font-size: 0.75rem; color: var(--color-text-muted); font-weight: 600; margin: 0 0 0.4rem; }
.justif-texto { font-size: 0.875rem; color: var(--color-text); margin: 0; }
.sem-evidencia { font-size: 0.85rem; color: var(--color-text-muted); display: flex; align-items: center; gap: 0.5rem; }
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.2s; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }
</style>
