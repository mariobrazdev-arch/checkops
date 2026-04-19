<template>
  <div class="app-loading-overlay" role="status" aria-label="Carregando">
    <div class="app-loading-spinner" aria-hidden="true">
      <svg viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
        <circle
          cx="25"
          cy="25"
          r="20"
          fill="none"
          stroke="var(--color-gold)"
          stroke-width="4"
          stroke-linecap="round"
          stroke-dasharray="100 28"
        />
      </svg>
    </div>
    <span class="sr-only">Carregando…</span>
  </div>
</template>

<style scoped>
.app-loading-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(17, 17, 17, 0.75);
  backdrop-filter: blur(2px);
}

.app-loading-spinner {
  width: 52px;
  height: 52px;
  animation: spin 0.9s linear infinite;
}

.app-loading-spinner svg {
  width: 100%;
  height: 100%;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}
</style>
