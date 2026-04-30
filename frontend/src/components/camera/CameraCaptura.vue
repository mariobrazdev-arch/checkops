<script setup>
/**
 * CameraCaptura.vue — RN-03
 * Câmera exclusivamente ao vivo (getUserMedia). Nunca <input type="file">.
 * Emits: @capturada({ fotos: [{base64, timestamp, deviceId, lat, lng}] })
 *        @cancelada
 *
 * Suporta múltiplas fotos por sessão (máx 5).
 * Compressão automática: max 1280x960, JPEG 72%.
 */
import { ref, computed, watch, onUnmounted } from 'vue'

const MAX_FOTOS = 5
const JPEG_QUALITY = 0.72
const MAX_W = 1280
const MAX_H = 960

const props = defineProps({
  aberta: { type: Boolean, required: true },
})
const emit = defineEmits(['capturada', 'cancelada'])

// ─── State ───────────────────────────────────────────────────────────────────
const videoEl    = ref(null)
const canvasEl   = ref(null)
const stream     = ref(null)
const preview    = ref(null)   // base64 atual em preview
const fase       = ref('live') // 'live' | 'preview' | 'galeria'
const erro       = ref('')
const geoLoading = ref(false)
const fotos      = ref([])     // [{base64, timestamp, deviceId, lat, lng}]

let coords   = { lat: null, lng: null }
let deviceId = null

const podeAdicionarMais = computed(() => fotos.value.length < MAX_FOTOS)

// ─── Geolocalização ──────────────────────────────────────────────────────────
function obterGeo() {
  return new Promise((resolve) => {
    if (!navigator.geolocation) { resolve(); return }
    geoLoading.value = true
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        coords.lat = pos.coords.latitude
        coords.lng = pos.coords.longitude
        geoLoading.value = false
        resolve()
      },
      () => { geoLoading.value = false; resolve() },
      { timeout: 5000, maximumAge: 30000 }
    )
  })
}

// ─── Câmera ──────────────────────────────────────────────────────────────────
async function abrirCamera() {
  erro.value = ''
  fase.value = 'live'
  preview.value = null

  if (fotos.value.length === 0) await obterGeo()

  try {
    const constraints = {
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
      audio: false,
    }
    stream.value = await navigator.mediaDevices.getUserMedia(constraints)

    const track = stream.value.getVideoTracks()[0]
    deviceId = track?.getSettings()?.deviceId ?? null

    await new Promise((r) => setTimeout(r, 50))
    if (videoEl.value) {
      videoEl.value.srcObject = stream.value
      await videoEl.value.play()
    }
  } catch {
    erro.value = 'Não foi possível acessar a câmera. Verifique as permissões do navegador.'
    fecharStream()
  }
}

function fecharStream() {
  if (stream.value) {
    stream.value.getTracks().forEach((t) => t.stop())
    stream.value = null
  }
}

// ─── Compressão ──────────────────────────────────────────────────────────────
function comprimirCanvas(canvas) {
  const w = canvas.width
  const h = canvas.height
  const ratio = Math.min(MAX_W / w, MAX_H / h, 1.0)

  if (ratio < 1.0) {
    const c2 = document.createElement('canvas')
    c2.width  = Math.round(w * ratio)
    c2.height = Math.round(h * ratio)
    c2.getContext('2d').drawImage(canvas, 0, 0, c2.width, c2.height)
    return c2.toDataURL('image/jpeg', JPEG_QUALITY)
  }
  return canvas.toDataURL('image/jpeg', JPEG_QUALITY)
}

// ─── Captura ─────────────────────────────────────────────────────────────────
function capturar() {
  if (!videoEl.value || !canvasEl.value) return
  const video = videoEl.value
  const canvas = canvasEl.value
  canvas.width  = video.videoWidth  || 1280
  canvas.height = video.videoHeight || 720
  canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height)
  preview.value = comprimirCanvas(canvas)
  fase.value = 'preview'
  fecharStream()
}

function refazer() {
  preview.value = null
  fase.value = 'live'
  abrirCamera()
}

function confirmarFoto() {
  fotos.value.push({
    base64:    preview.value,
    timestamp: Math.floor(Date.now() / 1000),
    deviceId,
    lat: coords.lat,
    lng: coords.lng,
  })
  preview.value = null

  if (podeAdicionarMais.value) {
    fase.value = 'galeria'
  } else {
    confirmarTudo()
  }
}

function adicionarMais() {
  fase.value = 'live'
  abrirCamera()
}

function removerFoto(idx) {
  fotos.value.splice(idx, 1)
}

function confirmarTudo() {
  emit('capturada', { fotos: fotos.value })
  fecharStream()
}

function cancelar() {
  fecharStream()
  emit('cancelada')
}

// ─── Watch ───────────────────────────────────────────────────────────────────
watch(() => props.aberta, (aberta) => {
  if (aberta) {
    fotos.value  = []
    preview.value = null
    fase.value   = 'live'
    erro.value   = ''
    abrirCamera()
  } else {
    fecharStream()
    fase.value  = 'live'
    fotos.value = []
    preview.value = null
    erro.value  = ''
  }
}, { immediate: true })

onUnmounted(() => fecharStream())
</script>

<template>
  <Teleport to="body">
    <Transition name="slide-up">
      <div v-if="aberta" class="overlay">
        <div class="camera-modal">
          <!-- Header -->
          <div class="modal-header">
            <span class="modal-titulo">
              <i class="pi pi-camera" />
              <template v-if="fase === 'live'">Tirar foto</template>
              <template v-else-if="fase === 'preview'">Confirmar foto</template>
              <template v-else>Fotos ({{ fotos.length }}/{{ MAX_FOTOS }})</template>
            </span>
            <button class="btn-close" @click="cancelar"><i class="pi pi-times" /></button>
          </div>

          <!-- Conteúdo -->
          <div class="modal-body">
            <!-- Erro -->
            <div v-if="erro" class="erro-box">
              <i class="pi pi-exclamation-triangle" />
              <p>{{ erro }}</p>
              <button class="btn-tentar" @click="abrirCamera">Tentar novamente</button>
            </div>

            <!-- Loading geo -->
            <div v-else-if="geoLoading" class="loading-box">
              <i class="pi pi-spin pi-spinner" />
              <p>Obtendo localização...</p>
            </div>

            <!-- Câmera ao vivo -->
            <template v-else-if="fase === 'live'">
              <div class="viewfinder">
                <video ref="videoEl" class="video" autoplay muted playsinline />
                <div class="viewfinder-guia" />
                <div v-if="fotos.length > 0" class="badge-fotos">{{ fotos.length }}/{{ MAX_FOTOS }}</div>
              </div>
              <div class="actions">
                <button class="btn-capturar" @click="capturar">
                  <div class="btn-capturar-inner" />
                </button>
              </div>
            </template>

            <!-- Preview da foto recém tirada -->
            <template v-else-if="fase === 'preview'">
              <div class="preview-wrap">
                <img :src="preview" class="preview-img" alt="Preview" />
              </div>
              <div class="actions actions--preview">
                <button class="btn-ghost" @click="refazer">
                  <i class="pi pi-refresh" /> Refazer
                </button>
                <button class="btn-confirmar" @click="confirmarFoto">
                  <i class="pi pi-check" /> Usar esta foto
                </button>
              </div>
            </template>

            <!-- Galeria de fotos capturadas -->
            <template v-else-if="fase === 'galeria'">
              <div class="galeria">
                <div v-for="(f, i) in fotos" :key="i" class="galeria-item">
                  <img :src="f.base64" class="galeria-img" :alt="`Foto ${i + 1}`" />
                  <button class="btn-remover-foto" @click="removerFoto(i)" title="Remover">
                    <i class="pi pi-times" />
                  </button>
                  <span class="galeria-num">{{ i + 1 }}</span>
                </div>
              </div>
              <div class="actions actions--galeria">
                <button v-if="podeAdicionarMais" class="btn-ghost" @click="adicionarMais">
                  <i class="pi pi-plus" /> Adicionar foto
                </button>
                <button class="btn-confirmar" @click="confirmarTudo">
                  <i class="pi pi-check" /> Concluir ({{ fotos.length }} {{ fotos.length === 1 ? 'foto' : 'fotos' }})
                </button>
              </div>
            </template>
          </div>

          <canvas ref="canvasEl" style="display:none" />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.9);
  display: flex; align-items: flex-end;
  z-index: 2000;
}

.camera-modal {
  background: var(--color-bg);
  width: 100%;
  border-radius: 20px 20px 0 0;
  max-height: 95vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--color-border);
  flex-shrink: 0;
}
.modal-titulo { font-weight: 600; color: var(--color-text); display: flex; align-items: center; gap: 0.5rem; }
.btn-close { background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 1.125rem; padding: 0.25rem; }
.btn-close:hover { color: var(--color-text); }

.modal-body { flex: 1; overflow: hidden; display: flex; flex-direction: column; min-height: 0; }

/* Loading / Erro */
.loading-box, .erro-box {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 1rem; padding: 2rem; color: var(--color-text-muted);
}
.erro-box { color: var(--status-atrasada); }
.erro-box p { text-align: center; font-size: 0.9rem; margin: 0; }
.btn-tentar { background: var(--color-surface-2); border: 1px solid var(--color-border); color: var(--color-text); padding: 0.5rem 1.25rem; border-radius: 8px; cursor: pointer; }

/* Viewfinder */
.viewfinder {
  position: relative;
  flex: 1;
  background: #000;
  overflow: hidden;
  max-height: 60vh;
}
.video { width: 100%; height: 100%; object-fit: cover; display: block; }
.viewfinder-guia {
  position: absolute; inset: 10%;
  border: 2px solid rgba(201,168,76,0.5);
  border-radius: 8px;
  pointer-events: none;
}
.badge-fotos {
  position: absolute; top: 0.75rem; right: 0.75rem;
  background: var(--color-gold); color: #111;
  border-radius: 999px; padding: 0.2rem 0.6rem;
  font-size: 0.75rem; font-weight: 700;
}

/* Preview */
.preview-wrap { flex: 1; overflow: hidden; max-height: 60vh; background: #000; display: flex; align-items: center; justify-content: center; }
.preview-img { max-width: 100%; max-height: 100%; object-fit: contain; display: block; }

/* Galeria */
.galeria {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
  padding: 1rem;
  overflow-y: auto;
  align-content: start;
}
.galeria-item {
  position: relative;
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid var(--color-border);
}
.galeria-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.btn-remover-foto {
  position: absolute; top: 0.25rem; right: 0.25rem;
  background: rgba(0,0,0,0.7); border: none; color: #fff;
  border-radius: 50%; width: 24px; height: 24px;
  font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.galeria-num {
  position: absolute; bottom: 0.25rem; left: 0.25rem;
  background: rgba(0,0,0,0.6); color: #fff;
  border-radius: 4px; padding: 0.1rem 0.35rem; font-size: 0.7rem;
}

/* Actions */
.actions {
  display: flex; align-items: center; justify-content: center;
  padding: 1.25rem 1.25rem; gap: 1rem; background: var(--color-bg);
  flex-shrink: 0;
}
.actions--preview { gap: 0.75rem; }
.actions--galeria { gap: 0.75rem; padding: 1rem 1.25rem; }

.btn-capturar {
  width: 70px; height: 70px;
  border-radius: 50%; background: white; border: 4px solid var(--color-gold);
  display: flex; align-items: center; justify-content: center; cursor: pointer;
  transition: transform 0.1s;
}
.btn-capturar:active { transform: scale(0.93); }
.btn-capturar-inner { width: 50px; height: 50px; background: var(--color-gold); border-radius: 50%; }

.btn-ghost {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: var(--color-surface-2); color: var(--color-text-muted);
  border: 1px solid var(--color-border); padding: 0.65rem 1.25rem;
  border-radius: 10px; font-size: 0.9rem; cursor: pointer; white-space: nowrap;
}
.btn-confirmar {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: var(--color-gold); color: #111; font-weight: 700;
  border: none; padding: 0.65rem 1.5rem; border-radius: 10px; font-size: 0.9rem; cursor: pointer;
  flex: 1;
}
.btn-confirmar:hover { background: var(--color-gold-light); }

/* Slide-up */
.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.3s ease, opacity 0.3s; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); opacity: 0; }

@media (min-width: 641px) {
  .overlay { align-items: center; }
  .camera-modal { border-radius: 16px; max-width: 480px; margin: auto; max-height: 85vh; }
}
</style>
