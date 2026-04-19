<script setup>
/**
 * CameraCaptura.vue — RN-03
 * Câmera exclusivamente ao vivo (getUserMedia). Nunca <input type="file">.
 * Emits: @capturada({ base64, timestamp, deviceId, lat, lng })
 *        @cancelada
 */
import { ref, watch, onUnmounted } from 'vue'

const props = defineProps({
  aberta: { type: Boolean, required: true },
})
const emit = defineEmits(['capturada', 'cancelada'])

// ─── State ───────────────────────────────────────────────────────────────────
const videoEl   = ref(null)
const canvasEl  = ref(null)
const stream    = ref(null)
const capturada = ref(null)   // base64 data URL após captura
const fase      = ref('live') // 'live' | 'preview'
const erro      = ref('')
const geoLoading = ref(false)

let coords = { lat: null, lng: null }
let deviceId = null

// ─── Geolocalização ───────────────────────────────────────────────────────────
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
      () => {
        // GPS negado — lat/lng ficam null (RN-03 permite)
        geoLoading.value = false
        resolve()
      },
      { timeout: 5000, maximumAge: 30000 }
    )
  })
}

// ─── Câmera ───────────────────────────────────────────────────────────────────
async function abrirCamera() {
  erro.value = ''
  fase.value = 'live'
  capturada.value = null

  await obterGeo()

  try {
    const constraints = { video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false }
    stream.value = await navigator.mediaDevices.getUserMedia(constraints)

    const track = stream.value.getVideoTracks()[0]
    deviceId = track?.getSettings()?.deviceId ?? null

    // Precisa aguardar o DOM montar o <video>
    await new Promise((r) => setTimeout(r, 50))
    if (videoEl.value) {
      videoEl.value.srcObject = stream.value
      await videoEl.value.play()
    }
  } catch (e) {
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

// ─── Captura ──────────────────────────────────────────────────────────────────
function capturar() {
  if (!videoEl.value || !canvasEl.value) return
  const video = videoEl.value
  const canvas = canvasEl.value
  canvas.width  = video.videoWidth  || 1280
  canvas.height = video.videoHeight || 720
  const ctx = canvas.getContext('2d')
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
  capturada.value = canvas.toDataURL('image/jpeg', 0.85)
  fase.value = 'preview'
  fecharStream()
}

function refazer() {
  capturada.value = null
  fase.value = 'live'
  abrirCamera()
}

function confirmar() {
  emit('capturada', {
    base64: capturada.value,
    timestamp: Math.floor(Date.now() / 1000),
    deviceId: deviceId,
    lat: coords.lat,
    lng: coords.lng,
  })
  fecharStream()
}

function cancelar() {
  fecharStream()
  emit('cancelada')
}

// ─── Watch abertura ───────────────────────────────────────────────────────────
watch(() => props.aberta, (aberta) => {
  if (aberta) {
    abrirCamera()
  } else {
    fecharStream()
    fase.value = 'live'
    capturada.value = null
    erro.value = ''
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
              {{ fase === 'live' ? 'Tirar foto' : 'Confirmar foto' }}
            </span>
            <button class="btn-close" @click="cancelar"><i class="pi pi-times" /></button>
          </div>

          <!-- Conteúdo -->
          <div class="modal-body">
            <!-- Erro de permissão -->
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
              </div>
              <div class="actions">
                <button class="btn-capturar" @click="capturar">
                  <div class="btn-capturar-inner" />
                </button>
              </div>
            </template>

            <!-- Preview -->
            <template v-else-if="fase === 'preview'">
              <div class="preview-wrap">
                <img :src="capturada" class="preview-img" alt="Preview da foto" />
              </div>
              <div class="actions actions--preview">
                <button class="btn-ghost" @click="refazer">
                  <i class="pi pi-refresh" /> Refazer
                </button>
                <button class="btn-confirmar" @click="confirmar">
                  <i class="pi pi-check" /> Usar esta foto
                </button>
              </div>
            </template>
          </div>

          <!-- Canvas oculto para captura -->
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
}
.modal-titulo { font-weight: 600; color: var(--color-text); display: flex; align-items: center; gap: 0.5rem; }
.btn-close { background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 1.125rem; padding: 0.25rem; }
.btn-close:hover { color: var(--color-text); }

.modal-body { flex: 1; overflow: hidden; display: flex; flex-direction: column; }

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

/* Preview */
.preview-wrap { flex: 1; overflow: hidden; max-height: 60vh; background: #000; display: flex; align-items: center; justify-content: center; }
.preview-img { max-width: 100%; max-height: 100%; object-fit: contain; display: block; }

/* Botão de captura */
.actions {
  display: flex; align-items: center; justify-content: center;
  padding: 1.5rem 1.25rem; gap: 1rem; background: var(--color-bg);
}
.actions--preview { gap: 0.75rem; }
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
  border-radius: 10px; font-size: 0.9rem; cursor: pointer;
}
.btn-confirmar {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: var(--color-gold); color: #111; font-weight: 700;
  border: none; padding: 0.65rem 1.5rem; border-radius: 10px; font-size: 0.9rem; cursor: pointer;
  flex: 1;
}
.btn-confirmar:hover { background: var(--color-gold-light); }

/* Slide-up transition */
.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.3s ease, opacity 0.3s; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); opacity: 0; }

@media (min-width: 641px) {
  .overlay { align-items: center; }
  .camera-modal { border-radius: 16px; max-width: 480px; margin: auto; max-height: 85vh; }
}
</style>
