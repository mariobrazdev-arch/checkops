<script setup>
/**
 * FotoViewer.vue
 * Exibe 1 ou mais fotos com metadados. Aceita prop `fotos` (array) ou
 * props legadas (fotoUrl + metadata) para compatibilidade.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  // Array de fotos: [{id, url, ordem}] — preferido
  fotos: { type: Array, default: null },
  // Legado (single photo)
  fotoUrl:       { type: String,          default: null },
  fotoTimestamp: { type: String,          default: null },
  deviceId:      { type: String,          default: null },
  lat:           { type: [Number, String], default: null },
  lng:           { type: [Number, String], default: null },
  mapaUrl:       { type: String,          default: null },
})

// Normaliza para array interno
const lista = computed(() => {
  if (props.fotos?.length) return props.fotos.map(f => ({ url: f.url }))
  if (props.fotoUrl)       return [{ url: props.fotoUrl }]
  return []
})

const total    = computed(() => lista.value.length)
const temFotos = computed(() => total.value > 0)

// Navegação
const idx = ref(0)
const fotoAtual = computed(() => lista.value[idx.value] ?? null)

function anterior() { if (idx.value > 0) idx.value-- }
function proximo()  { if (idx.value < total.value - 1) idx.value++ }

function onKey(e) {
  if (!temFotos.value || total.value < 2) return
  if (e.key === 'ArrowLeft')  anterior()
  if (e.key === 'ArrowRight') proximo()
}

// URL expirando (só relevante para fotos assinadas)
const carregadoEm = ref(Date.now())
const agora       = ref(Date.now())
let timer = null
onMounted(() => {
  timer = setInterval(() => { agora.value = Date.now() }, 60_000)
  window.addEventListener('keydown', onKey)
})
onUnmounted(() => { clearInterval(timer); window.removeEventListener('keydown', onKey) })

const minutosDecorridos = computed(() => Math.floor((agora.value - carregadoEm.value) / 60_000))
const urlExpirando      = computed(() => minutosDecorridos.value >= 55)
const temGps            = computed(() => props.lat != null && props.lng != null)
const dataCaptura       = computed(() => {
  if (!props.fotoTimestamp) return null
  return new Date(props.fotoTimestamp).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
})

function ampliar() {
  if (fotoAtual.value?.url) window.open(fotoAtual.value.url, '_blank', 'noopener,noreferrer')
}
</script>

<template>
  <div class="foto-viewer">
    <!-- Sem foto -->
    <div v-if="!temFotos" class="sem-foto">
      <i class="pi pi-image" />
      <span>Sem foto registrada</span>
    </div>

    <template v-else>
      <!-- Container da imagem -->
      <div class="foto-container">
        <img :src="fotoAtual.url" alt="Evidência fotográfica" class="foto-img" @click="ampliar" />

        <!-- Navegação (só se múltiplas) -->
        <template v-if="total > 1">
          <button class="nav-btn nav-btn--prev" @click="anterior" :disabled="idx === 0">
            <i class="pi pi-chevron-left" />
          </button>
          <button class="nav-btn nav-btn--next" @click="proximo" :disabled="idx === total - 1">
            <i class="pi pi-chevron-right" />
          </button>
          <div class="foto-counter">{{ idx + 1 }} / {{ total }}</div>
        </template>

        <div class="ampliar-hint" @click="ampliar" title="Ampliar">
          <i class="pi pi-arrows-alt" />
        </div>
      </div>

      <!-- Miniaturas (se múltiplas) -->
      <div v-if="total > 1" class="thumbs">
        <button
          v-for="(f, i) in lista" :key="i"
          class="thumb-btn"
          :class="{ ativo: i === idx }"
          @click="idx = i"
        >
          <img :src="f.url" :alt="`Foto ${i + 1}`" class="thumb-img" />
        </button>
      </div>

      <!-- Aviso URL expirando -->
      <div v-if="urlExpirando" class="aviso-expiracao">
        <i class="pi pi-clock" /> Link próximo de expirar. Recarregue para renovar.
      </div>

      <!-- Metadados (da primeira foto) -->
      <div class="metadados">
        <div v-if="dataCaptura" class="meta-item">
          <i class="pi pi-calendar" /><span>{{ dataCaptura }}</span>
        </div>
        <div v-if="deviceId" class="meta-item">
          <i class="pi pi-mobile" />
          <span class="device-id" :title="deviceId">{{ deviceId.slice(0, 20) }}{{ deviceId.length > 20 ? '…' : '' }}</span>
        </div>
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
  gap: 0.5rem; padding: 2.5rem;
  background: var(--color-surface-2); border-radius: 10px;
  color: var(--color-text-muted); font-size: 0.9rem;
}
.sem-foto .pi { font-size: 2rem; }

.foto-container {
  position: relative; border-radius: 10px; overflow: hidden; background: #000;
  cursor: zoom-in;
}
.foto-img { width: 100%; display: block; max-height: 380px; object-fit: contain; }

/* Contador */
.foto-counter {
  position: absolute; top: 0.6rem; right: 0.6rem;
  background: rgba(0,0,0,0.65); color: #fff;
  border-radius: 99px; padding: 0.2rem 0.65rem; font-size: 0.78rem; font-weight: 600;
  backdrop-filter: blur(4px);
}

/* Botões nav */
.nav-btn {
  position: absolute; top: 50%; transform: translateY(-50%);
  background: rgba(0,0,0,0.55); border: none; color: #fff;
  width: 36px; height: 36px; border-radius: 50%; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.9rem; backdrop-filter: blur(4px); transition: background .15s;
}
.nav-btn:hover:not(:disabled) { background: rgba(0,0,0,0.8); }
.nav-btn:disabled { opacity: .3; cursor: not-allowed; }
.nav-btn--prev { left: 0.5rem; }
.nav-btn--next { right: 0.5rem; }

.ampliar-hint {
  position: absolute; bottom: 0.5rem; right: 0.5rem;
  background: rgba(0,0,0,0.5); color: #fff; border-radius: 6px;
  padding: 0.25rem 0.4rem; font-size: 0.8rem; opacity: 0; transition: opacity .2s;
}
.foto-container:hover .ampliar-hint { opacity: 1; }

/* Miniaturas */
.thumbs { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.thumb-btn {
  width: 56px; height: 56px; border-radius: 6px; overflow: hidden;
  border: 2px solid transparent; cursor: pointer; padding: 0;
  background: none; transition: border-color .15s;
  flex-shrink: 0;
}
.thumb-btn.ativo { border-color: var(--color-gold); }
.thumb-btn:hover { border-color: var(--color-text-muted); }
.thumb-img { width: 100%; height: 100%; object-fit: cover; display: block; }

.aviso-expiracao {
  display: flex; align-items: center; gap: 0.5rem;
  background: rgba(239,68,68,0.12); border: 1px solid var(--status-atrasada);
  color: var(--status-atrasada); border-radius: 8px; padding: 0.6rem 0.9rem; font-size: 0.85rem;
}

.metadados { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.meta-item { display: flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; color: var(--color-text-muted); }
.device-id { font-family: monospace; font-size: 0.75rem; }

.badge-gps {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.78rem; font-weight: 600;
  text-decoration: none;
}
.badge-gps--ok  { background: rgba(34,197,94,0.15);   color: var(--status-realizada); }
.badge-gps--sem { background: rgba(107,114,128,0.15); color: var(--status-nao-realizada); }
</style>
