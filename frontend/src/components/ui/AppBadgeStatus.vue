<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
})

const config = {
  pendente:      { label: 'Pendente',       icon: '',                          color: 'var(--status-pendente)',      bg: 'rgba(245,158,11,0.12)' },
  realizada:     { label: 'Realizada',      icon: 'pi pi-check',               color: 'var(--status-realizada)',     bg: 'rgba(34,197,94,0.12)' },
  atrasada:      { label: 'Em atraso',      icon: 'pi pi-exclamation-triangle', color: 'var(--status-atrasada)',      bg: 'rgba(239,68,68,0.15)' },
  nao_realizada: { label: 'Não realizada',  icon: '',                          color: 'var(--status-nao-realizada)', bg: 'rgba(107,114,128,0.12)' },
}

const c = computed(() => config[props.status] ?? { label: props.status, color: 'var(--color-text-muted)', bg: 'transparent' })
</script>

<template>
  <span class="badge" :style="{ color: c.color, background: c.bg }">
    <i v-if="c.icon" :class="c.icon" class="badge-icon" />{{ c.label }}
  </span>
</template>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.2rem 0.6rem;
  border-radius: 99px;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  white-space: nowrap;
}
.badge-icon { font-size: 0.68rem; }
</style>
