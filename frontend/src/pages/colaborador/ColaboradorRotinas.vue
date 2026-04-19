<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRotinasStore } from '../../stores/rotinas.store.js'
import { useAuthStore } from '../../stores/auth.store.js'
import { usePushNotifications } from '../../composables/usePushNotifications.js'
import RotinaCard from '../../components/shared/RotinaCard.vue'
import CameraCaptura from '../../components/camera/CameraCaptura.vue'
import JustificativaModal from '../../components/shared/JustificativaModal.vue'

const store       = useRotinasStore()
const authStore   = useAuthStore()
const push        = usePushNotifications()

// ─── Saudação contextual ──────────────────────────────────────────────────────
const saudacao = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Bom dia'
  if (h < 18) return 'Boa tarde'
  return 'Boa noite'
})

const dataHoje = new Intl.DateTimeFormat('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date())

// ─── Conformidade ─────────────────────────────────────────────────────────────
const conformidade = computed(() => store.conformidade)

const progressoWidth = computed(() => `${conformidade.value.percentual}%`)

// ─── Rotinas ordenadas ────────────────────────────────────────────────────────
const prioridade = { atrasada: 0, pendente: 1, realizada: 2, nao_realizada: 3 }
const rotinasOrdenadas = computed(() =>
  [...store.rotinasHoje].sort((a, b) => (prioridade[a.status] ?? 9) - (prioridade[b.status] ?? 9))
)

// ─── Camera ───────────────────────────────────────────────────────────────────
const cameraAberta      = ref(false)
const rotinaRespondendo = ref(null)

function iniciarSim(rotina) {
  rotinaRespondendo.value = rotina
  if (rotina.rotina?.foto_obrigatoria) {
    cameraAberta.value = true
  } else {
    // Sem foto — responde direto
    store.responderSim(rotina.id, {})
    rotinaRespondendo.value = null
  }
}

async function onFotoCapturada(payload) {
  cameraAberta.value = false
  if (!rotinaRespondendo.value) return
  await store.responderSim(rotinaRespondendo.value.id, {
    foto_base64:   payload.base64,
    foto_timestamp: payload.timestamp,
    foto_device_id: payload.deviceId,
    foto_lat:       payload.lat,
    foto_lng:       payload.lng,
  })
  rotinaRespondendo.value = null
}

function onCameraCancelada() {
  cameraAberta.value = false
  rotinaRespondendo.value = null
}

// ─── Justificativa ────────────────────────────────────────────────────────────
const justifAberta = ref(false)

function iniciarNao(rotina) {
  rotinaRespondendo.value = rotina
  justifAberta.value = true
}

async function onJustifConfirmada(justificativa) {
  justifAberta.value = false
  if (!rotinaRespondendo.value) return
  await store.responderNao(rotinaRespondendo.value.id, justificativa)
  rotinaRespondendo.value = null
}

function onJustifCancelada() {
  justifAberta.value = false
  rotinaRespondendo.value = null
}

// ─── Polling ──────────────────────────────────────────────────────────────────
let timer = null

onMounted(() => {
  store.buscarHoje()
  push.verificarEstado()
  timer = setInterval(() => store.buscarHoje(), 60_000)
})

onUnmounted(() => clearInterval(timer))
</script>

<template>
  <div class="page">
    <!-- Banner push (US-20) -->
    <div v-if="push.permissao.value === 'default'" class="banner-push">
      <span><i class="pi pi-bell" /> Ative notificações para não perder rotinas</span>
      <button class="btn-ativar-push" @click="push.solicitarPermissao()" :disabled="push.carregando.value">
        {{ push.carregando.value ? 'Ativando...' : 'Ativar' }}
      </button>
    </div>

    <!-- Header boas-vindas -->
    <div class="header">
      <div class="header-info">
        <h1 class="saudacao">{{ saudacao }}, {{ authStore.user?.nome?.split(' ')[0] }}!</h1>
        <p class="data-hoje">{{ dataHoje }}</p>
      </div>
      <button class="btn-atualizar" @click="store.buscarHoje()" :disabled="store.loading" title="Atualizar">
        <i :class="['pi', store.loading ? 'pi-spin pi-spinner' : 'pi-refresh']" />
      </button>
    </div>

    <!-- Barra de conformidade -->
    <div class="conformidade-box">
      <div class="conf-topo">
        <span class="conf-label">Progresso de hoje</span>
        <span class="conf-nums">
          <strong>{{ conformidade.concluidas }}</strong> de {{ conformidade.total }} concluídas
          <span class="conf-pct">{{ conformidade.percentual }}%</span>
        </span>
      </div>
      <div class="barra-bg">
        <div class="barra-fg" :style="{ width: progressoWidth }" />
      </div>
    </div>

    <!-- Loading inicial -->
    <div v-if="store.loading && rotinasOrdenadas.length === 0" class="loading-box">
      <i class="pi pi-spin pi-spinner" /> Carregando rotinas...
    </div>

    <!-- Empty state -->
    <div v-else-if="rotinasOrdenadas.length === 0" class="empty-state">
      <i class="pi pi-check-circle" style="font-size:2rem;color:var(--status-realizada)" />
      <p>Nenhuma rotina para hoje.</p>
    </div>

    <!-- Lista de rotinas -->
    <div v-else class="lista">
      <RotinaCard
        v-for="rotina in rotinasOrdenadas"
        :key="rotina.id"
        :rotina="rotina"
        @responder-sim="iniciarSim"
        @responder-nao="iniciarNao"
      />
    </div>

    <!-- Camera (RN-03) -->
    <CameraCaptura
      :aberta="cameraAberta"
      @capturada="onFotoCapturada"
      @cancelada="onCameraCancelada"
    />

    <!-- Justificativa -->
    <JustificativaModal
      :aberta="justifAberta"
      :rotina-id="rotinaRespondendo?.id ?? null"
      :obrigatoria="rotinaRespondendo?.rotina?.justif_obrigatoria ?? false"
      @confirmada="onJustifConfirmada"
      @cancelada="onJustifCancelada"
    />
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1rem; padding-bottom: 1.5rem; }

/* Banner push */
.banner-push {
  background: rgba(201,168,76,.12);
  border: 1px solid rgba(201,168,76,.35);
  border-radius: 10px;
  padding: .75rem 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  font-size: .875rem;
  color: var(--color-text);
}
.btn-ativar-push {
  background: var(--color-gold);
  border: none;
  border-radius: 8px;
  color: #111;
  font-weight: 600;
  font-size: .8rem;
  padding: .35rem .875rem;
  cursor: pointer;
  white-space: nowrap;
}
.btn-ativar-push:disabled { opacity: .6; cursor: not-allowed; }

/* Header */
.header { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
.saudacao { margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--color-text); }
.data-hoje { margin: 0.15rem 0 0; font-size: 0.8125rem; color: var(--color-text-muted); text-transform: capitalize; }
.btn-atualizar {
  background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text-muted); padding: 0.5rem; border-radius: 8px;
  cursor: pointer; flex-shrink: 0; font-size: 1rem;
}
.btn-atualizar:hover { color: var(--color-gold); border-color: var(--color-gold); }
.btn-atualizar:disabled { opacity: 0.5; cursor: not-allowed; }

/* Conformidade */
.conformidade-box {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 1rem 1.125rem;
  display: flex; flex-direction: column; gap: 0.625rem;
}
.conf-topo { display: flex; align-items: center; justify-content: space-between; }
.conf-label { font-size: 0.8125rem; color: var(--color-text-muted); }
.conf-nums { font-size: 0.8125rem; color: var(--color-text); }
.conf-pct { color: var(--color-gold); font-weight: 700; margin-left: 0.4rem; }
.barra-bg { height: 8px; background: var(--color-surface-2); border-radius: 99px; overflow: hidden; }
.barra-fg { height: 100%; background: var(--color-gold); border-radius: 99px; transition: width 0.5s ease; }

/* Lista */
.lista { display: flex; flex-direction: column; gap: 0.75rem; }

/* States */
.loading-box { display: flex; align-items: center; justify-content: center; gap: 0.75rem; color: var(--color-text-muted); padding: 2rem; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; padding: 3rem 1rem; color: var(--color-text-muted); }
.empty-state p { margin: 0; font-size: 0.9rem; }
</style>
