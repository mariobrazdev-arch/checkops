<script setup>
import { ref, computed, onMounted } from 'vue'
import { relatoriosService } from '../../services/relatorios.service.js'
import { validacaoService } from '../../services/validacao.service.js'
import { useSetores } from '../../composables/useSetores.js'
import { useUiStore } from '../../stores/ui.store.js'
import AppBadgeStatus from '../../components/ui/AppBadgeStatus.vue'
import RotinaDetalhe from '../../components/shared/RotinaDetalhe.vue'

const uiStore = useUiStore()
const { setores, buscar: buscarSetores } = useSetores()

const itens       = ref([])
const loading     = ref(false)
const meta        = ref({})
const pagina      = ref(1)
const selecionada = ref(null)

// Reabrir
const reabrirAberto     = ref(false)
const reabrirJustif     = ref('')
const reabrirCarregando = ref(false)
const reabrirErro       = ref('')

// Filtros
const filtroSetor       = ref('')
const filtroColaborador = ref('')
const filtroDataInicio  = ref('')
const filtroDataFim     = ref('')

async function buscar(pg = 1) {
  loading.value = true
  try {
    const params = { page: pg, per_page: 20, status: 'realizada' }
    if (filtroSetor.value)       params.setor_id    = filtroSetor.value
    if (filtroColaborador.value) params.colaborador  = filtroColaborador.value
    if (filtroDataInicio.value)  params.data_inicio  = filtroDataInicio.value
    if (filtroDataFim.value)     params.data_fim     = filtroDataFim.value

    const { data } = await relatoriosService.listar(params)
    itens.value  = pg === 1 ? (data.data ?? []) : [...itens.value, ...(data.data ?? [])]
    meta.value   = data.meta ?? {}
    pagina.value = pg
  } catch {
    uiStore.addToast({ severity: 'error', summary: 'Erro ao carregar validações' })
  } finally {
    loading.value = false
  }
}

function carregarMais() { buscar(pagina.value + 1) }
const temMais = computed(() => (meta.value.current_page ?? 0) < (meta.value.last_page ?? 0))

function aplicarFiltros() { buscar(1) }
function limparFiltros() {
  filtroSetor.value = ''; filtroColaborador.value = ''
  filtroDataInicio.value = ''; filtroDataFim.value = ''
  buscar(1)
}
const temFiltroAtivo = computed(() =>
  filtroSetor.value || filtroColaborador.value || filtroDataInicio.value || filtroDataFim.value
)

let debounce = null
function onColabInput() {
  clearTimeout(debounce)
  debounce = setTimeout(() => buscar(1), 400)
}

async function abrirDetalhe(item) {
  try {
    const { data } = await validacaoService.detalhe(item.id)
    selecionada.value = data.data ?? data
  } catch {
    uiStore.addToast({ severity: 'error', summary: 'Erro ao abrir detalhe' })
  }
}

function fmtDataHora(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function abrirReabrir() {
  reabrirJustif.value = ''
  reabrirErro.value   = ''
  reabrirAberto.value = true
}

async function confirmarReabrir() {
  if (reabrirJustif.value.trim().length < 20) {
    reabrirErro.value = 'Justificativa deve ter no mínimo 20 caracteres.'
    return
  }
  reabrirCarregando.value = true
  try {
    await validacaoService.reabrir(selecionada.value.id, reabrirJustif.value.trim())
    const idx = itens.value.findIndex(i => i.id === selecionada.value.id)
    if (idx !== -1) itens.value.splice(idx, 1)
    reabrirAberto.value = false
    selecionada.value   = null
    uiStore.addToast({ severity: 'success', summary: 'Rotina reaberta para o colaborador' })
  } catch (e) {
    reabrirErro.value = e?.response?.data?.message ?? 'Erro ao reabrir rotina'
  } finally {
    reabrirCarregando.value = false
  }
}

onMounted(async () => {
  await buscarSetores()
  buscar(1)
})
</script>

<template>
  <div class="page">
    <header class="page-header">
      <div>
        <h1 class="page-titulo">Validação de Fotos</h1>
        <p class="page-sub">Rotinas realizadas de todos os setores · evidências fotográficas</p>
      </div>
    </header>

    <!-- Filtros -->
    <div class="filtros">
      <select v-model="filtroSetor" class="f-input" @change="aplicarFiltros">
        <option value="">Todos os setores</option>
        <option v-for="s in setores" :key="s.id" :value="s.id">{{ s.nome }}</option>
      </select>
      <input
        v-model="filtroColaborador"
        type="text"
        class="f-input"
        placeholder="Buscar colaborador…"
        @input="onColabInput"
      />
      <input v-model="filtroDataInicio" type="date" class="f-input" @change="aplicarFiltros" />
      <input v-model="filtroDataFim" type="date" class="f-input" @change="aplicarFiltros" />
      <button v-if="temFiltroAtivo" class="btn-limpar" @click="limparFiltros">
        <i class="pi pi-times" /> Limpar
      </button>
    </div>

    <!-- Loading inicial -->
    <div v-if="loading && itens.length === 0" class="estado-centro">
      <i class="pi pi-spin pi-spinner" /> Carregando...
    </div>

    <!-- Vazio -->
    <div v-else-if="!loading && itens.length === 0" class="estado-vazio">
      <i class="pi pi-camera" />
      <p>Nenhuma rotina realizada encontrada.</p>
    </div>

    <!-- Contagem -->
    <div v-if="meta.total && !loading" class="total-bar">
      <span class="total-tag">{{ meta.total.toLocaleString('pt-BR') }} registros</span>
    </div>

    <!-- Cards -->
    <div v-if="itens.length" class="lista">
      <div
        v-for="item in itens"
        :key="item.id"
        class="card"
        @click="abrirDetalhe(item)"
      >
        <!-- Miniatura -->
        <div class="thumb-wrap">
          <template v-if="item.foto_url || item.fotos?.length">
            <img :src="item.fotos?.[0]?.url ?? item.foto_url" class="thumb" alt="Foto" />
            <span v-if="(item.fotos?.length ?? 0) > 1" class="thumb-count">{{ item.fotos.length }}</span>
          </template>
          <div v-else class="thumb thumb--sem">
            <i class="pi pi-image" />
          </div>
        </div>

        <!-- Dados -->
        <div class="card-corpo">
          <div class="card-topo">
            <span class="card-titulo">{{ item.rotina?.titulo ?? '—' }}</span>
            <AppBadgeStatus :status="item.status" />
          </div>
          <p class="card-setor"><i class="pi pi-sitemap" /> {{ item.rotina?.setor?.nome ?? '—' }}</p>
          <p class="card-colab"><i class="pi pi-user" /> {{ item.colaborador?.nome ?? '—' }}</p>
          <p class="card-data">
            <i class="pi pi-clock" /> {{ fmtDataHora(item.data_hora_resposta) }}
            <span v-if="item.foto_lat" class="gps-badge"><i class="pi pi-map-marker" /> GPS</span>
          </p>
        </div>

        <i class="pi pi-chevron-right card-chevron" />
      </div>
    </div>

    <!-- Carregar mais -->
    <div v-if="temMais && !loading" class="carregar-mais">
      <button class="btn-mais" @click="carregarMais">Carregar mais</button>
    </div>
    <div v-if="loading && itens.length > 0" class="loading-mais">
      <i class="pi pi-spin pi-spinner" /> Carregando…
    </div>

    <!-- Modal detalhe -->
    <RotinaDetalhe
      :rotina="selecionada"
      :pode-reabrir="true"
      @fechar="selecionada = null"
      @reabrir="abrirReabrir"
    />

    <!-- Modal reabrir -->
    <Teleport to="body">
      <Transition name="overlay">
        <div v-if="reabrirAberto" class="overlay" @click.self="reabrirAberto = false">
          <div class="reabrir-dialog">
            <div class="reabrir-header">
              <h3>Reabrir rotina</h3>
              <button class="btn-close" @click="reabrirAberto = false"><i class="pi pi-times" /></button>
            </div>
            <div class="reabrir-body">
              <p class="reabrir-info">
                A rotina voltará para <strong>pendente</strong> e o colaborador poderá respondê-la novamente.
              </p>
              <div class="field">
                <label>Por que está reabrindo? *</label>
                <textarea
                  v-model="reabrirJustif"
                  class="textarea"
                  rows="4"
                  placeholder="Descreva o motivo da reabertura (mínimo 20 caracteres)..."
                  :disabled="reabrirCarregando"
                />
                <span class="char-count" :class="{ 'char-count--ok': reabrirJustif.trim().length >= 20 }">
                  {{ reabrirJustif.trim().length }} / 20 mín.
                </span>
                <span v-if="reabrirErro" class="campo-erro">{{ reabrirErro }}</span>
              </div>
            </div>
            <div class="reabrir-footer">
              <button class="btn-cancelar" @click="reabrirAberto = false" :disabled="reabrirCarregando">Cancelar</button>
              <button class="btn-confirmar" @click="confirmarReabrir" :disabled="reabrirCarregando">
                <i v-if="reabrirCarregando" class="pi pi-spin pi-spinner" />
                {{ reabrirCarregando ? 'Reabrindo...' : 'Confirmar reabertura' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1.25rem; }
.page-header { margin-bottom: 0; }
.page-titulo { font-size: 1.375rem; font-weight: 700; color: var(--color-text); margin: 0 0 0.2rem; }
.page-sub { font-size: 0.8125rem; color: var(--color-text-muted); margin: 0; }

.filtros { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
.f-input {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text); padding: 0.5rem 0.75rem; border-radius: 8px;
  font-size: 0.875rem; outline: none; min-width: 140px;
}
.f-input:focus { border-color: var(--color-gold); }
.btn-limpar {
  background: none; border: 1px solid var(--color-border); color: var(--color-text-muted);
  padding: 0.45rem 0.9rem; border-radius: 8px; cursor: pointer; font-size: 0.83rem;
  display: inline-flex; align-items: center; gap: 0.35rem; white-space: nowrap;
}
.btn-limpar:hover { border-color: var(--color-gold); color: var(--color-gold); }

.estado-centro { display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 3rem; color: var(--color-text-muted); }
.estado-vazio { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; padding: 4rem 2rem; color: var(--color-text-muted); }
.estado-vazio .pi { font-size: 2.5rem; }

.total-bar { display: flex; align-items: center; }
.total-tag { background: rgba(201,168,76,.15); color: var(--color-gold); border-radius: 99px; padding: .2rem .7rem; font-size: .75rem; font-weight: 600; }

/* Cards */
.lista { display: flex; flex-direction: column; gap: 0.75rem; }
.card {
  display: flex; align-items: center; gap: 0.9rem;
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; padding: 0.85rem 1rem; cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}
.card:hover { border-color: var(--color-gold); background: var(--color-surface-2); }

.thumb-wrap { flex-shrink: 0; position: relative; }
.thumb { width: 64px; height: 64px; object-fit: cover; border-radius: 8px; display: block; }
.thumb-count {
  position: absolute; bottom: 3px; right: 3px;
  background: rgba(0,0,0,.7); color: #fff;
  font-size: 0.65rem; font-weight: 700; border-radius: 99px;
  padding: .1rem .35rem; backdrop-filter: blur(4px);
}
.thumb--sem {
  width: 64px; height: 64px; border-radius: 8px;
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  display: flex; align-items: center; justify-content: center;
  color: var(--color-text-muted); font-size: 1.25rem;
}

.card-corpo { flex: 1; min-width: 0; }
.card-topo { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.2rem; }
.card-titulo { font-weight: 600; color: var(--color-text); font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.card-setor  { font-size: 0.78rem; color: var(--color-gold); margin: 0 0 0.15rem; display: flex; align-items: center; gap: 0.3rem; }
.card-colab  { font-size: 0.78rem; color: var(--color-text-muted); margin: 0 0 0.15rem; display: flex; align-items: center; gap: 0.3rem; }
.card-data   { font-size: 0.78rem; color: var(--color-text-muted); margin: 0; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.gps-badge   { color: var(--status-realizada); font-size: 0.74rem; display: inline-flex; align-items: center; gap: 0.2rem; }
.card-chevron { color: var(--color-border); font-size: 0.85rem; flex-shrink: 0; }

.carregar-mais { display: flex; justify-content: center; margin-top: 0.5rem; }
.btn-mais {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text); padding: 0.6rem 1.75rem; border-radius: 10px; cursor: pointer; font-size: 0.9rem;
}
.btn-mais:hover { border-color: var(--color-gold); color: var(--color-gold); }
.loading-mais { display: flex; justify-content: center; align-items: center; gap: 0.5rem; color: var(--color-text-muted); font-size: 0.85rem; }

/* Reabrir modal */
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,.7); display: flex; align-items: center; justify-content: center; z-index: 10000; padding: 1rem; }
.reabrir-dialog { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; width: 100%; max-width: 460px; }
.reabrir-header { display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--color-border); }
.reabrir-header h3 { margin: 0; font-size: 1rem; font-weight: 600; color: var(--color-text); }
.btn-close { background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 1rem; }
.reabrir-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; }
.reabrir-info { font-size: 0.875rem; color: var(--color-text-muted); margin: 0; line-height: 1.5; }
.reabrir-info strong { color: var(--color-text); }
.field { display: flex; flex-direction: column; gap: 0.375rem; }
.field label { font-size: 0.8rem; color: var(--color-text-muted); font-weight: 500; }
.textarea { background: var(--color-surface-2); border: 1px solid var(--color-border); color: var(--color-text); padding: 0.625rem 0.75rem; border-radius: 8px; font-size: 0.875rem; outline: none; resize: vertical; font-family: inherit; line-height: 1.5; }
.textarea:focus { border-color: var(--color-gold); }
.char-count { font-size: 0.75rem; color: var(--color-text-muted); align-self: flex-end; }
.char-count--ok { color: var(--status-realizada); }
.campo-erro { font-size: 0.8rem; color: var(--status-atrasada); }
.reabrir-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.25rem; border-top: 1px solid var(--color-border); }
.btn-cancelar { background: transparent; border: 1px solid var(--color-border); color: var(--color-text-muted); padding: 0.55rem 1rem; border-radius: 8px; cursor: pointer; font-size: 0.875rem; }
.btn-confirmar { display: inline-flex; align-items: center; gap: 0.4rem; background: var(--status-atrasada); color: #fff; font-weight: 600; padding: 0.55rem 1.1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.875rem; }
.btn-confirmar:disabled { opacity: 0.6; cursor: not-allowed; }
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.2s; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }
</style>
