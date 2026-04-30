<script setup>
/**
 * RotinaDetalhe.vue
 * Modal de detalhe completo de uma RotinaDiariaDetalheResource.
 * Props: rotina (RotinaDiariaDetalheResource | null)
 * Emits: fechar
 */
import { computed } from 'vue'
import Dialog from 'primevue/dialog'
import AppBadgeStatus from '../ui/AppBadgeStatus.vue'
import FotoViewer from './FotoViewer.vue'

const props = defineProps({
  rotina:       { type: Object,  default: null },
  podeReabrir:  { type: Boolean, default: false },
})
const emit = defineEmits(['fechar', 'reabrir'])

const aberta = computed(() => props.rotina !== null)

function fmtDataHora(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function fmtData(str) {
  if (!str) return '—'
  const [y, m, d] = str.split('-')
  return `${d}/${m}/${y}`
}
</script>

<template>
  <Dialog
    :visible="aberta"
    @update:visible="emit('fechar')"
    :style="{ width: '95vw', maxWidth: '560px' }"
    :modal="true"
    :closable="true"
    :draggable="false"
    header="Detalhe da Execução"
    pt:root:style="background: var(--color-surface); border: 1px solid var(--color-border);"
    pt:header:style="background: var(--color-surface); color: var(--color-text); border-bottom: 1px solid var(--color-border); padding: 1rem 1.25rem;"
    pt:content:style="background: var(--color-surface); padding: 1.25rem; overflow-y: auto; max-height: 75vh;"
    pt:closeButton:style="color: var(--color-text-muted);"
  >
    <template v-if="rotina">
      <!-- Cabeçalho: status + título -->
      <div class="secao-topo">
        <AppBadgeStatus :status="rotina.status" />
        <h2 class="titulo-rotina">{{ rotina.rotina?.titulo ?? '—' }}</h2>
        <p class="data-rotina">{{ fmtData(rotina.data) }} · Previsto: {{ rotina.horario_previsto ?? '—' }}</p>
      </div>

      <!-- Colaborador -->
      <div v-if="rotina.colaborador" class="secao">
        <h3 class="secao-label">Colaborador</h3>
        <div class="colaborador-info">
          <i class="pi pi-user avatar" />
          <div>
            <p class="colab-nome">{{ rotina.colaborador.nome }}</p>
            <p class="colab-detalhe">Mat. {{ rotina.colaborador.matricula }} · {{ rotina.colaborador.cargo }}</p>
          </div>
        </div>
      </div>

      <!-- Resposta -->
      <div class="secao">
        <h3 class="secao-label">Resposta</h3>
        <div class="grade">
          <div class="grade-item">
            <span class="grade-chave">Hora da resposta</span>
            <span class="grade-valor">{{ fmtDataHora(rotina.data_hora_resposta) }}</span>
          </div>
        </div>
      </div>

      <!-- Justificativa (se nao_realizada ou se existir) -->
      <div v-if="rotina.justificativa" class="secao">
        <h3 class="secao-label">Justificativa</h3>
        <p class="justificativa-texto">{{ rotina.justificativa }}</p>
      </div>

      <!-- Foto + metadados -->
      <div class="secao">
        <h3 class="secao-label">
          Evidência fotográfica
          <a v-if="rotina.mapa_url" :href="rotina.mapa_url" target="_blank" rel="noopener noreferrer" class="link-mapa">
            <i class="pi pi-external-link" /> Ver no mapa
          </a>
        </h3>
        <FotoViewer
          :fotos="rotina.fotos?.length ? rotina.fotos : null"
          :foto-url="rotina.foto_url"
          :foto-timestamp="rotina.foto_timestamp"
          :device-id="rotina.foto_device_id"
          :lat="rotina.foto_lat"
          :lng="rotina.foto_lng"
          :mapa-url="rotina.mapa_url"
        />
      </div>

      <!-- Ação: reabrir -->
      <div v-if="podeReabrir && ['realizada','nao_realizada'].includes(rotina.status)" class="secao-reabrir">
        <button class="btn-reabrir" @click="emit('reabrir')">
          <i class="pi pi-replay" /> Reabrir para o colaborador
        </button>
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
.secao-topo { margin-bottom: 1.25rem; }
.titulo-rotina { font-size: 1.15rem; font-weight: 700; color: var(--color-text); margin: 0.4rem 0 0.2rem; }
.data-rotina   { font-size: 0.83rem; color: var(--color-text-muted); margin: 0; }

.secao { margin-bottom: 1.25rem; }
.secao-label {
  font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
  color: var(--color-text-muted); margin: 0 0 0.6rem; display: flex; align-items: center; gap: 0.5rem;
}
.link-mapa {
  font-size: 0.78rem; color: var(--color-gold); text-decoration: none; font-weight: 600;
  text-transform: none; letter-spacing: 0; display: inline-flex; align-items: center; gap: 0.3rem;
}
.link-mapa:hover { color: var(--color-gold-light); }

.colaborador-info {
  display: flex; align-items: center; gap: 0.75rem;
  background: var(--color-surface-2); border-radius: 10px; padding: 0.75rem 1rem;
}
.avatar { font-size: 1.5rem; color: var(--color-gold); flex-shrink: 0; }
.colab-nome    { font-weight: 600; color: var(--color-text); margin: 0 0 0.15rem; }
.colab-detalhe { font-size: 0.8rem; color: var(--color-text-muted); margin: 0; }

.grade { display: flex; flex-direction: column; gap: 0.35rem; }
.grade-item { display: flex; justify-content: space-between; font-size: 0.85rem; }
.grade-chave { color: var(--color-text-muted); }
.grade-valor  { color: var(--color-text); font-weight: 500; }

.justificativa-texto {
  background: var(--color-surface-2); border-left: 3px solid var(--color-gold);
  padding: 0.75rem 1rem; border-radius: 0 8px 8px 0; font-size: 0.9rem;
  color: var(--color-text); margin: 0; line-height: 1.5;
}

.secao-reabrir { margin-top: 0.5rem; padding-top: 1rem; border-top: 1px solid var(--color-border); }
.btn-reabrir {
  width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  padding: 0.7rem 1rem; border-radius: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 600;
  background: transparent; border: 1px solid var(--status-atrasada); color: var(--status-atrasada);
  transition: background 0.15s;
}
.btn-reabrir:hover { background: rgba(239,68,68,0.08); }
</style>
