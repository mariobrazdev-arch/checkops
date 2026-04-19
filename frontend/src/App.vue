<script setup>
import { computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import LayoutAuth from './layouts/LayoutAuth.vue'
import LayoutAdmin from './layouts/LayoutAdmin.vue'
import LayoutGestor from './layouts/LayoutGestor.vue'
import LayoutColaborador from './layouts/LayoutColaborador.vue'
import LayoutSuperAdmin from './layouts/LayoutSuperAdmin.vue'
import AppLoading from './components/ui/AppLoading.vue'
import { useUiStore } from './stores/ui.store.js'

const route = useRoute()
const toast = useToast()
const uiStore = useUiStore()

const layouts = {
  LayoutAuth,
  LayoutAdmin,
  LayoutGestor,
  LayoutColaborador,
  LayoutSuperAdmin,
}

const currentLayout = computed(() => {
  const layoutName = route.meta.layout || 'LayoutAuth'
  return layouts[layoutName] || LayoutAuth
})

// Ouve CustomEvent emitido pelo api.js (evita dependência circular)
function onApiToast(event) {
  const { severity, summary, detail } = event.detail
  toast.add({ severity, summary, detail, life: 4000 })
}

onMounted(() => window.addEventListener('app:toast', onApiToast))
onBeforeUnmount(() => window.removeEventListener('app:toast', onApiToast))
</script>

<template>
  <component :is="currentLayout">
    <RouterView />
  </component>
  <AppLoading v-if="uiStore.loadingGlobal" />
  <Toast position="top-right" />
</template>
