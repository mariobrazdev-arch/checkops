<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  aberta:     { type: Boolean, required: true },
  rotinaId:   { type: String,  default: null },
  obrigatoria:{ type: Boolean, default: false },
})
const emit = defineEmits(['confirmada', 'cancelada'])

const texto = ref('')
const salvando = ref(false)

const chars = computed(() => texto.value.length)
const podeSalvar = computed(() => chars.value >= 20)

function cancelar() {
  texto.value = ''
  emit('cancelada')
}

async function confirmar() {
  if (!podeSalvar.value) return
  salvando.value = true
  await new Promise((r) => setTimeout(r, 0)) // flush
  emit('confirmada', texto.value)
  texto.value = ''
  salvando.value = false
}

// Reset ao fechar
import { watch } from 'vue'
watch(() => props.aberta, (v) => { if (!v) texto.value = '' })
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div v-if="aberta" class="overlay" @click.self="cancelar">
        <div class="modal">
          <div class="modal-header">
            <h3 class="modal-titulo">
              <i class="pi pi-comment" /> Justificativa
            </h3>
            <button class="btn-close" aria-label="Fechar modal" @click="cancelar">
              <i class="pi pi-times" aria-hidden="true" />
            </button>
          </div>

          <div class="modal-body">
            <p class="dica">
              <template v-if="obrigatoria">
                Esta rotina exige uma justificativa para marcar como não realizada.
              </template>
              <template v-else>
                Descreva o motivo para registrar como não realizada.
              </template>
            </p>

            <div class="textarea-wrap">
              <label for="justificativa-texto" class="sr-only">Justificativa</label>
              <textarea
                id="justificativa-texto"
                v-model="texto"
                class="textarea"
                rows="4"
                maxlength="500"
                placeholder="Descreva o motivo (mínimo 20 caracteres)..."
                :aria-invalid="chars > 0 && !podeSalvar"
                aria-describedby="justificativa-hint"
                autofocus
              />
              <span class="chars" :class="{ 'chars--ok': podeSalvar, 'chars--warn': !podeSalvar && chars > 0 }">
                {{ chars }}/500
              </span>
            </div>

            <p
              id="justificativa-hint"
              v-if="chars > 0 && !podeSalvar"
              class="hint-min"
              role="alert"
            >
              Mínimo 20 caracteres (faltam {{ 20 - chars }})
            </p>
          </div>

          <div class="modal-footer">
            <button class="btn-ghost" @click="cancelar">Cancelar</button>
            <button class="btn-confirmar" :disabled="!podeSalvar || salvando" @click="confirmar">
              <i v-if="salvando" class="pi pi-spin pi-spinner" />
              Confirmar
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.7);
  display: flex; align-items: flex-end;
  z-index: 1500;
}
.modal {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 20px 20px 0 0;
  width: 100%;
  display: flex; flex-direction: column;
}
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.25rem; border-bottom: 1px solid var(--color-border);
}
.modal-titulo { margin: 0; font-size: 1rem; font-weight: 600; color: var(--color-text); display: flex; align-items: center; gap: 0.5rem; }
.btn-close { background: none; border: none; cursor: pointer; color: var(--color-text-muted); }
.btn-close:hover { color: var(--color-text); }
.modal-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; }
.dica { margin: 0; font-size: 0.85rem; color: var(--color-text-muted); }
.textarea-wrap { position: relative; }
.textarea {
  width: 100%; background: var(--color-surface-2); border: 1px solid var(--color-border);
  color: var(--color-text); padding: 0.75rem 0.875rem; border-radius: 10px;
  font-size: 0.9rem; font-family: inherit; resize: vertical;
  outline: none; box-sizing: border-box;
}
.textarea:focus { border-color: var(--color-gold); }
.chars {
  position: absolute; bottom: 8px; right: 10px;
  font-size: 0.72rem; color: var(--color-text-muted);
  pointer-events: none;
}
.chars--ok { color: var(--status-realizada); }
.chars--warn { color: var(--status-atrasada); }
.hint-min { margin: 0; font-size: 0.78rem; color: var(--status-pendente); }
.modal-footer {
  display: flex; gap: 0.75rem; justify-content: flex-end;
  padding: 1rem 1.25rem; border-top: 1px solid var(--color-border);
}
.btn-ghost {
  background: transparent; color: var(--color-text-muted); font-size: 0.9rem;
  padding: 0.55rem 1.1rem; border: 1px solid var(--color-border); border-radius: 8px; cursor: pointer;
}
.btn-ghost:hover { color: var(--color-text); }
.btn-confirmar {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: var(--color-gold); color: #111; font-weight: 700;
  padding: 0.55rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.9rem;
}
.btn-confirmar:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-confirmar:not(:disabled):hover { background: var(--color-gold-light); }

.sr-only {
  position: absolute;
  width: 1px; height: 1px;
  padding: 0; margin: -1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}

.overlay-enter-active, .overlay-leave-active { transition: opacity 0.2s; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }
.overlay-enter-active .modal, .overlay-leave-active .modal { transition: transform 0.25s ease; }
.overlay-enter-from .modal, .overlay-leave-to .modal { transform: translateY(100%); }

@media (min-width: 641px) {
  .overlay { align-items: center; }
  .modal { border-radius: 16px; max-width: 440px; margin: auto; }
}
</style>
