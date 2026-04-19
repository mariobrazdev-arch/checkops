<script setup>
/**
 * FotoViewer.vue
 * Exibe foto com metadados GPS, device e aviso de URL próxima de expirar.
 * Props:
 *   fotoUrl      — URL assinada temporária (1h TTL)
 *   fotoTimestamp — ISO8601 da captura
 *   deviceId     — identificador do dispositivo
 *   lat/lng      — coordenadas GPS (null = sem GPS)
 *   mapaUrl      — link Google Maps pré-calculado pela API
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  fotoUrl:       { type: String,  default: null },
  fotoTimestamp: { type: String,  default: null },
  deviceId:      { type: String,  default: null },
  lat:           { type: [Number, String], default: null },
  lng:           { type: [Number, String], default: null },
  mapaUrl:       { type: String,  default: null },
})

// Marca o momento em que o componente montou (≈ quando a URL foi gerada)
const carregadoEm = ref(Date.now())
const agora       = ref(Date.now())
let   timer       = null

onMounted(()    => { timer = setInterval(() => { agora.value = Date.now() }, 60_000) })
onUnmounted(()  => clearInterval(timer))

const minutosDecorridos = computed(() => Math.floor((agora.value - carregadoEm.value) / 60_000))
const urlExpirando      = computed(() => minutosDecorridos.value >= 55)
const temGps            = computed(() => props.lat != null && props.lng != null)

const dataCaptura = computed(() => {
  if (!props.fotoTimestamp) return null
  return new Date(props.fotoTimestamp).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
})

function ampliar() {
  if (props.fotoUrl) window.open(props.fotoUrl, '_blank', 'noopener,noreferrer')
}
</script>

<template>
  <div class="foto-viewer">
    <!-- Sem foto -->
    <div v-if="!fotoUrl" class="sem-foto">
      <i class="pi pi-image" />
      <span>Sem foto registrada</span>
    </div>

    <template v-else>
      <!-- Imagem clicável para ampliar -->
      <div class="foto-container" @click="ampliar" title="Clique para ampliar">
        <img :src="fotoUrl" alt="Evidência fotográfica" class="foto-img" />
        <div class="ampliar-overlay"><i class="pi pi-arrows-alt" /></div>
      </div>

      <!-- Aviso URL expirando -->
      <div v-if="urlExpirando" class="aviso-expiracao">
        <i class="pi pi-clock" />
        Link da foto próximo de expirar. Recarregue para renovar.
      </div>

      <!-- Metadados -->
      <div class="metadados">
        <div v-if="dataCaptura" class="meta-item">
          <i class="pi pi-calendar" />
          <span>{{ dataCaptura }}</span>
        </div>
        <div v-if="deviceId" class="meta-item">
          <i class="pi pi-mobile" />
          <span class="device-id" :title="deviceId">{{ deviceId.slice(0, 20) }}{{ deviceId.length > 20 ? '…' : '' }}</span>
        </div>

        <!-- Badge GPS -->
        <a v-if="temGps && mapaUrl" :href="mapaUrl" target="_blank" rel="noopener noreferrer" class="badge-gps badge-gps--ok">
          <i class="pi pi-map-marker" /> GPS disponível
        </a>
        <span v-else-if="temGps" class="badge-gps badge-gps--ok">
          <i class="pi pi-map-marker" /> GPS disponível
        </span>
        <span v-else class="badge-gps badge-gps--sem">
          <i class="pi pi-ban" /> Sem localização
        </span>
      </div>
    </template>
  </div>
</template>

<style scoped>
.foto-viewer { display: flex; flex-direction: column; gap: 0.75rem; }

.sem-foto {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.5rem; padding: 2.5rem; background: var(--color-surface-2);
  border-radius: 10px; color: var(--color-text-muted); font-size: 0.9rem;
}
.sem-foto .pi { font-size: 2rem; }

.foto-container {
  position: relative; cursor: zoom-in;
  border-radius: 10px; overflow: hidden;
  background: #000;
}
.foto-img { width: 100%; display: block; max-height: 400px; object-fit: contain; }
.ampliar-overlay {
  position: absolute; inset: 0; background: rgba(0,0,0,0);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.5rem; transition: background 0.2s;
}
.foto-container:hover .ampliar-overlay { background: rgba(0,0,0,0.35); }

.aviso-expiracao {
  display: flex; align-items: center; gap: 0.5rem;
  background: rgba(239,68,68,0.12); border: 1px solid var(--status-atrasada);
  color: var(--status-atrasada); border-radius: 8px; padding: 0.6rem 0.9rem; font-size: 0.85rem;
}

.metadados {
  display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;
}
.meta-item {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.8rem; color: var(--color-text-muted);
}
.device-id { font-family: monospace; font-size: 0.75rem; }

.badge-gps {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.78rem; font-weight: 600;
  text-decoration: none;
}
.badge-gps--ok { background: rgba(34,197,94,0.15); color: var(--status-realizada); }
.badge-gps--sem { background: rgba(107,114,128,0.15); color: var(--status-nao-realizada); }
</style>
