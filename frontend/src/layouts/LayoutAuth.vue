<script setup>
// Layout sem nav — para login, esqueci senha, redefinir senha
</script>

<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-logo">
        <div class="logo-mark">
          <span class="logo-check">CHECK</span><span class="logo-ops">OPS</span>
        </div>
        <p class="logo-tagline">Check-in de Operações</p>
      </div>
      <slot />
    </div>
  </div>
</template>

<style scoped>
.auth-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-bg);
  padding: 1rem;
}

.auth-card {
  width: 100%;
  max-width: 400px;
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 2rem;
}

.auth-logo {
  text-align: center;
  margin-bottom: 2rem;
}
.logo-mark {
  font-size: 1.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
}
.logo-tagline {
  margin: 0.35rem 0 0;
  font-size: 0.78rem;
  color: var(--color-text-muted);
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.logo-check {
  color: var(--color-gold);
}

.logo-ops {
  color: var(--color-text);
}
</style>
