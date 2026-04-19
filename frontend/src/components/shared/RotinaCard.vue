<script setup>
import { computed } from 'vue'
import AppBadgeStatus from '../ui/AppBadgeStatus.vue'

const props = defineProps({
  rotina: { type: Object, required: true },
})

const emit = defineEmits(['responder-sim', 'responder-nao'])

const podeResponder = computed(() =>
  props.rotina.status === 'pendente' || props.rotina.status === 'atrasada'
)

function formatarDataHora(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div
    class="card"
    :class="{
      'card--atrasada': rotina.status === 'atrasada',
      'card--realizada': rotina.status === 'realizada',
      'card--nao-realizada': rotina.status === 'nao_realizada',
    }"
  >
    <div class="card-header">
      <div class="card-info">
        <span class="card-titulo">{{ rotina.rotina?.titulo ?? rotina.titulo }}</span>
        <span class="card-horario">
          <i class="pi pi-clock" />
          {{ rotina.rotina?.horario_previsto ?? rotina.horario_previsto }}
        </span>
      </div>
      <AppBadgeStatus :status="rotina.status" />
    </div>

    <div v-if="rotina.reaberta_justificativa" class="card-reaberta">
      <i class="pi pi-replay" /> <strong>Reaberta pelo gestor:</strong> {{ rotina.reaberta_justificativa }}
    </div>

    <div v-if="rotina.rotina?.foto_obrigatoria" class="card-hint">
      <i class="pi pi-camera" /> Foto obrigatória
      <span v-if="rotina.rotina?.so_camera"> · somente câmera</span>
    </div>

    <div v-if="rotina.data_hora_resposta" class="card-resposta">
      <i class="pi pi-check-circle" />
      Respondida em {{ formatarDataHora(rotina.data_hora_resposta) }}
    </div>

    <div v-if="rotina.justificativa" class="card-justificativa">
      <i class="pi pi-info-circle" /> {{ rotina.justificativa }}
    </div>

    <div v-if="rotina.status === 'atrasada'" class="card-alerta-atraso">
      <i class="pi pi-exclamation-triangle" /> Rotina em atraso — responda o quanto antes
    </div>

    <div v-if="podeResponder" class="card-actions">
      <button
        class="btn-nao"
        aria-label="Marcar como não realizada"
        @click="emit('responder-nao', rotina)"
      >
        <i class="pi pi-times" aria-hidden="true" /> Não realizar
      </button>
      <button
        class="btn-sim"
        :class="{ 'btn-sim--atrasada': rotina.status === 'atrasada' }"
        aria-label="Realizar rotina"
        @click="emit('responder-sim', rotina)"
      >
        <i class="pi pi-check" aria-hidden="true" /> Realizar
      </button>
    </div>
  </div>
</template>

<style scoped>
.card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-left: 4px solid var(--color-border);
  border-radius: 10px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
  transition: border-color 0.15s;
}
.card--atrasada   { border-left-color: var(--status-atrasada); }
.card--realizada  { border-left-color: var(--status-realizada); opacity: 0.8; }
.card--nao-realizada { border-left-color: var(--status-nao-realizada); opacity: 0.7; }

.card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
.card-info { display: flex; flex-direction: column; gap: 0.2rem; }
.card-titulo { font-weight: 600; color: var(--color-text); font-size: 0.9375rem; }
.card-horario { font-size: 0.8rem; color: var(--color-text-muted); display: flex; align-items: center; gap: 0.3rem; }

.card-reaberta {
  font-size: 0.8rem; color: var(--color-text);
  background: rgba(201,168,76,0.08); border: 1px solid rgba(201,168,76,0.25);
  border-left: 3px solid var(--color-gold);
  border-radius: 0 6px 6px 0; padding: 0.5rem 0.75rem;
  line-height: 1.45;
}
.card-reaberta strong { color: var(--color-gold); }
.card-hint { font-size: 0.78rem; color: var(--color-gold); display: flex; align-items: center; gap: 0.35rem; }
.card-resposta { font-size: 0.78rem; color: var(--status-realizada); display: flex; align-items: center; gap: 0.35rem; }
.card-justificativa {
  font-size: 0.8rem; color: var(--color-text-muted);
  background: var(--color-surface-2); border-radius: 6px; padding: 0.5rem 0.625rem;
  display: flex; align-items: flex-start; gap: 0.35rem;
}

.card-actions { display: flex; gap: 0.5rem; margin-top: 0.25rem; }
.btn-sim, .btn-nao {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.4rem;
  min-height: 52px;
  padding: 0.7rem 0.875rem; border: none; border-radius: 8px;
  font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: opacity 0.15s;
}
.btn-sim  { background: var(--status-realizada); color: #fff; }
.btn-sim--atrasada { background: var(--status-atrasada); animation: pulso 1.8s ease-in-out infinite; }
.btn-nao  { background: var(--color-surface-2); color: var(--color-text-muted); border: 1px solid var(--color-border); }
.btn-sim:hover  { opacity: 0.88; }
.btn-nao:hover  { color: var(--status-atrasada); border-color: var(--status-atrasada); }

.card-alerta-atraso {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.78rem; font-weight: 600;
  color: var(--status-atrasada);
  background: rgba(239,68,68,0.08);
  border: 1px solid rgba(239,68,68,0.25);
  border-radius: 6px; padding: 0.4rem 0.65rem;
}

@keyframes pulso {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.75; }
}
</style>
