<script setup>
const props = defineProps({
  visivel: { type: Boolean, default: false },
  titulo: { type: String, default: 'Confirmar ação' },
  variante: { type: String, default: 'danger' }, // danger | warn | info
  labelConfirmar: { type: String, default: 'Confirmar' },
  labelCancelar: { type: String, default: 'Cancelar' },
  carregando: { type: Boolean, default: false },
})

const emit = defineEmits(['update:visivel', 'confirmar', 'cancelar'])

function fechar() {
  emit('update:visivel', false)
  emit('cancelar')
}

function confirmar() {
  emit('confirmar')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div v-if="visivel" class="overlay" @click.self="fechar">
        <div class="dialog" role="alertdialog" :aria-label="titulo">
          <div class="dialog-header">
            <div class="dialog-icon" :class="`dialog-icon--${variante}`">
              <i v-if="variante === 'danger'" class="pi pi-exclamation-triangle" />
              <i v-else-if="variante === 'warn'" class="pi pi-exclamation-circle" />
              <i v-else class="pi pi-info-circle" />
            </div>
            <h2 class="dialog-titulo">{{ titulo }}</h2>
          </div>

          <div class="dialog-body">
            <slot>
              <p>Deseja confirmar esta ação?</p>
            </slot>
          </div>

          <div class="dialog-footer">
            <button class="btn-cancelar" :disabled="carregando" @click="fechar">
              {{ labelCancelar }}
            </button>
            <button
              class="btn-confirmar"
              :class="`btn-confirmar--${variante}`"
              :disabled="carregando"
              @click="confirmar"
            >
              <span v-if="carregando" class="spinner" aria-hidden="true" />
              {{ labelConfirmar }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.65);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.dialog {
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 1.5rem;
  width: 100%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
  gap: 1.125rem;
}

.dialog-header {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.dialog-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
  flex-shrink: 0;
}

.dialog-icon--danger {
  background-color: color-mix(in srgb, var(--status-atrasada) 15%, transparent);
  color: var(--status-atrasada);
}

.dialog-icon--warn {
  background-color: color-mix(in srgb, var(--status-pendente) 15%, transparent);
  color: var(--status-pendente);
}

.dialog-icon--info {
  background-color: color-mix(in srgb, var(--color-gold) 15%, transparent);
  color: var(--color-gold);
}

.dialog-titulo {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
}

.dialog-body {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  line-height: 1.5;
}

.dialog-body :deep(p) {
  margin: 0;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 0.25rem;
}

.btn-cancelar {
  background: none;
  border: 1px solid var(--color-border);
  color: var(--color-text-muted);
  border-radius: 8px;
  padding: 0.625rem 1.125rem;
  cursor: pointer;
  font-size: 0.875rem;
  transition: border-color 0.15s, color 0.15s;
}

.btn-cancelar:hover:not(:disabled) {
  border-color: var(--color-text-muted);
  color: var(--color-text);
}

.btn-cancelar:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-confirmar {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  border: none;
  border-radius: 8px;
  padding: 0.625rem 1.125rem;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 600;
  transition: opacity 0.15s;
  min-height: 40px;
}

.btn-confirmar--danger {
  background-color: var(--status-atrasada);
  color: #fff;
}

.btn-confirmar--warn {
  background-color: var(--status-pendente);
  color: #111111;
}

.btn-confirmar--info {
  background-color: var(--color-gold);
  color: #111111;
}

.btn-confirmar:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  display: inline-block;
  width: 0.875rem;
  height: 0.875rem;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: rgba(255,255,255,0.9);
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

.btn-confirmar--info .spinner,
.btn-confirmar--warn .spinner {
  border-color: rgba(17,17,17,0.3);
  border-top-color: rgba(17,17,17,0.9);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Transition */
.overlay-enter-active,
.overlay-leave-active {
  transition: opacity 0.2s;
}

.overlay-enter-active .dialog,
.overlay-leave-active .dialog {
  transition: transform 0.2s, opacity 0.2s;
}

.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}

.overlay-enter-from .dialog,
.overlay-leave-to .dialog {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
}
</style>
