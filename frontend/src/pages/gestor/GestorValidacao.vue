<script setup>
/**
 * GestorValidacao.vue — US-14
 * Lista rotinas_diarias realizadas do setor para validação visual de foto.
 * Mobile-first: cards em DataView.
 */
import { ref, computed, onMounted } from 'vue'
import DataView from 'primevue/dataview'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Calendar from 'primevue/calendar'
import ProgressBar from 'primevue/progressbar'
import AppBadgeStatus from '../../components/ui/AppBadgeStatus.vue'
import RotinaDetalhe from '../../components/shared/RotinaDetalhe.vue'
import { validacaoService } from '../../services/validacao.service.js'
import { useUiStore } from '../../stores/ui.store.js'

const uiStore = useUiStore()

// ─── Estado ───────────────────────────────────────────────────────────────────
const itens        = ref([])
const loading      = ref(false)
const meta         = ref({})
const pagina       = ref(1)
const selecionada  = ref(null)

// Reabrir
const reabrirAberto      = ref(false)
const reabrirJustif      = ref('')
const reabrirCarregando  = ref(false)
const reabrirErro        = ref('')

// Filtros
const buscaColaborador = ref('')
const periodo          = ref(null)  // [Date, Date] | null

// ─── Busca ────────────────────────────────────────────────────────────────────
async function buscar(pg = 1) {
  loading.value = true
  try {
    const params = { page: pg, status: 'realizada' }
    if (buscaColaborador.value.trim()) params.colaborador = buscaColaborador.value.trim()
    if (periodo.value?.[0]) params.data_inicio = fmtParaApi(periodo.value[0])
    if (periodo.value?.[1]) params.data_fim    = fmtParaApi(periodo.value[1])

    const { data } = await validacaoService.listar(params)
    itens.value = pg === 1 ? (data.data ?? []) : [...itens.value, ...(data.data ?? [])]
    meta.value  = data.meta ?? {}
    pagina.value = pg
  } catch {
    uiStore.addToast({ severity: 'error', summary: 'Erro ao carregar validações' })
  } finally {
    loading.value = false
  }
}

function carregarMais() { buscar(pagina.value + 1) }
const temMais = computed(() => meta.value.current_page < meta.value.last_page)

function fmtParaApi(d) {
  return d.toISOString().slice(0, 10)
}

function fmtDataHora(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit',
  })
}

async function abrirDetalhe(item) {
  // Busca recurso completo para garantir dados frescos
  try {
    const { data } = await validacaoService.detalhe(item.id)
    selecionada.value = data.data ?? data
  } catch {
    uiStore.addToast({ severity: 'error', summary: 'Erro ao abrir detalhe' })
  }
}

let debounce = null
function onBuscaInput() {
  clearTimeout(debounce)
  debounce = setTimeout(() => buscar(1), 400)
}

function onPeriodoChange() { buscar(1) }
function limparFiltros() {
  buscaColaborador.value = ''
  periodo.value = null
  buscar(1)
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
    const { data } = await validacaoService.reabrir(selecionada.value.id, reabrirJustif.value.trim())
    // Atualiza o item na lista e fecha os modais
    const idx = itens.value.findIndex(i => i.id === selecionada.value.id)
    if (idx !== -1) itens.value.splice(idx, 1) // sai da lista de realizadas
    reabrirAberto.value = false
    selecionada.value   = null
    uiStore.addToast({ severity: 'success', summary: 'Rotina reaberta para o colaborador' })
  } catch (e) {
    reabrirErro.value = e?.response?.data?.message ?? 'Erro ao reabrir rotina'
  } finally {
    reabrirCarregando.value = false
  }
}

onMounted(() => buscar(1))
</script>

<template>
  <div class="page-validacao">
    <header class="page-header">
      <h1 class="page-titulo">Validação de Fotos</h1>
      <p class="page-sub">Rotinas realizadas · evidências fotográficas do setor</p>
    </header>

    <!-- Filtros -->
    <div class="filtros">
      <div class="filtro-item">
        <span class="p-input-icon-left filtro-busca">
          <i class="pi pi-search" />
          <InputText
            v-model="buscaColaborador"
            placeholder="Buscar colaborador…"
            class="w-full"
            @input="onBuscaInput"
          />
        </span>
      </div>
      <div class="filtro-item">
        <Calendar
          v-model="periodo"
          selectionMode="range"
          dateFormat="dd/mm/yy"
          placeholder="Período"
          :showIcon="true"
          :numberOfMonths="1"
          @update:modelValue="onPeriodoChange"
          pt:root:style="background:var(--color-surface-2)"
        />
      </div>
      <button v-if="buscaColaborador || periodo" class="btn-limpar" @click="limparFiltros">
        <i class="pi pi-times" /> Limpar
      </button>
    </div>

    <!-- Loading inicial -->
    <ProgressBar v-if="loading && itens.length === 0" mode="indeterminate" style="height:3px;margin-bottom:1rem" />

    <!-- Estado vazio -->
    <div v-else-if="!loading && itens.length === 0" class="empty-state">
      <i class="pi pi-camera" />
      <p>Nenhuma rotina realizada encontrada.</p>
    </div>

    <!-- Lista -->
    <DataView v-else :value="itens" layout="list">
      <template #list="{ items }">
        <div class="lista-validacao">
          <div
            v-for="item in items"
            :key="item.id"
            class="card-validacao"
            @click="abrirDetalhe(item)"
          >
            <!-- Miniatura -->
            <div class="thumb-wrap">
              <img v-if="item.foto_url" :src="item.foto_url" class="thumb" alt="Foto" />
              <div v-else class="thumb thumb--sem-foto">
                <i class="pi pi-image" />
              </div>
            </div>

            <!-- Dados -->
            <div class="card-corpo">
              <div class="card-linha-topo">
                <span class="card-titulo">{{ item.rotina?.titulo ?? '—' }}</span>
                <AppBadgeStatus :status="item.status" />
              </div>
              <p class="card-colab">
                <i class="pi pi-user" /> {{ item.colaborador?.nome ?? '—' }}
              </p>
              <p class="card-data">
                <i class="pi pi-clock" /> {{ fmtDataHora(item.data_hora_resposta) }}
                <span v-if="item.foto_lat" class="gps-badge">
                  <i class="pi pi-map-marker" /> GPS
                </span>
              </p>
            </div>

            <i class="pi pi-chevron-right card-chevron" />
          </div>
        </div>
      </template>
    </DataView>

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

    <!-- Modal justificativa de reabertura -->
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
.page-validacao { padding: 1.25rem; max-width: 900px; margin: 0 auto; }

.page-header { margin-bottom: 1.5rem; }
.page-titulo { font-size: 1.35rem; font-weight: 700; color: var(--color-text); margin: 0 0 0.2rem; }
.page-sub    { font-size: 0.85rem; color: var(--color-text-muted); margin: 0; }

.filtros {
  display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;
  margin-bottom: 1.25rem;
}
.filtro-item { flex: 1; min-width: 200px; }
.filtro-busca { width: 100%; display: flex; align-items: center; }
.btn-limpar {
  background: none; border: 1px solid var(--color-border); color: var(--color-text-muted);
  padding: 0.45rem 0.9rem; border-radius: 8px; cursor: pointer; font-size: 0.83rem;
  display: inline-flex; align-items: center; gap: 0.35rem; white-space: nowrap;
}
.btn-limpar:hover { border-color: var(--color-gold); color: var(--color-gold); }

.empty-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.75rem; padding: 4rem 2rem; color: var(--color-text-muted);
}
.empty-state .pi { font-size: 2.5rem; }

.lista-validacao { display: flex; flex-direction: column; gap: 0.75rem; }

.card-validacao {
  display: flex; align-items: center; gap: 0.9rem;
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; padding: 0.85rem 1rem; cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}
.card-validacao:hover { border-color: var(--color-gold); background: var(--color-surface-2); }

.thumb-wrap { flex-shrink: 0; }
.thumb {
  width: 60px; height: 60px; object-fit: cover;
  border-radius: 8px; display: block;
}
.thumb--sem-foto {
  width: 60px; height: 60px; border-radius: 8px;
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  display: flex; align-items: center; justify-content: center;
  color: var(--color-text-muted); font-size: 1.25rem;
}

.card-corpo { flex: 1; min-width: 0; }
.card-linha-topo { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.25rem; }
.card-titulo { font-weight: 600; color: var(--color-text); font-size: 0.92rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.card-colab  { font-size: 0.8rem; color: var(--color-text-muted); margin: 0 0 0.2rem; display: flex; align-items: center; gap: 0.3rem; }
.card-data   { font-size: 0.8rem; color: var(--color-text-muted); margin: 0; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.gps-badge   { color: var(--status-realizada); font-size: 0.76rem; display: inline-flex; align-items: center; gap: 0.2rem; }

.card-chevron { color: var(--color-border); font-size: 0.85rem; flex-shrink: 0; }

.carregar-mais { display: flex; justify-content: center; margin-top: 1.25rem; }
.btn-mais {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text); padding: 0.6rem 1.75rem; border-radius: 10px; cursor: pointer; font-size: 0.9rem;
}
.btn-mais:hover { border-color: var(--color-gold); color: var(--color-gold); }

.loading-mais { display: flex; justify-content: center; align-items: center; gap: 0.5rem; color: var(--color-text-muted); margin-top: 1rem; font-size: 0.85rem; }

/* Modal reabrir */
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 10000; padding: 1rem; }
.reabrir-dialog {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 12px; width: 100%; max-width: 460px; display: flex; flex-direction: column;
}
.reabrir-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--color-border);
}
.reabrir-header h3 { margin: 0; font-size: 1rem; font-weight: 600; color: var(--color-text); }
.btn-close { background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 1rem; }
.reabrir-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; }
.reabrir-info { font-size: 0.875rem; color: var(--color-text-muted); margin: 0; line-height: 1.5; }
.reabrir-info strong { color: var(--color-text); }
.field { display: flex; flex-direction: column; gap: 0.375rem; }
.field label { font-size: 0.8rem; color: var(--color-text-muted); font-weight: 500; }
.textarea {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text); padding: 0.625rem 0.75rem; border-radius: 8px;
  font-size: 0.875rem; outline: none; resize: vertical; font-family: inherit; line-height: 1.5;
}
.textarea:focus { border-color: var(--color-gold); }
.char-count { font-size: 0.75rem; color: var(--color-text-muted); align-self: flex-end; }
.char-count--ok { color: var(--status-realizada); }
.campo-erro { font-size: 0.8rem; color: var(--status-atrasada); }
.reabrir-footer {
  display: flex; justify-content: flex-end; gap: 0.75rem;
  padding: 1rem 1.25rem; border-top: 1px solid var(--color-border);
}
.btn-cancelar {
  background: transparent; border: 1px solid var(--color-border); color: var(--color-text-muted);
  padding: 0.55rem 1rem; border-radius: 8px; cursor: pointer; font-size: 0.875rem;
}
.btn-confirmar {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: var(--status-atrasada); color: #fff; font-weight: 600;
  padding: 0.55rem 1.1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.875rem;
}
.btn-confirmar:disabled { opacity: 0.6; cursor: not-allowed; }
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.2s; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }
</style>
