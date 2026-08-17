<script setup lang="ts">
import Button from 'primevue/button'
import { useTheme } from '@/composables/useTheme'

const { scheme, toggle } = useTheme()
</script>

<template>
  <header class="site-header">
    <div class="page bar">
      <span class="mark">pike<span class="colons">::</span>portfolio</span>

      <Button
        :icon="scheme === 'dark' ? 'pi pi-sun' : 'pi pi-moon'"
        :aria-label="scheme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
        variant="text"
        rounded
        severity="secondary"
        @click="toggle"
      />
    </div>
  </header>
</template>

<style scoped>
.site-header {
  position: sticky;
  top: 0;
  z-index: 10;
  background: color-mix(in srgb, var(--p-content-background) 85%, transparent);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--p-content-border-color);
}

.bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  min-height: 4rem;
}

.mark {
  font-family: var(--font-display);
  font-size: 1.0625rem;
  font-weight: 600;
  letter-spacing: -0.01em;
}

.colons {
  color: var(--p-primary-color);
}
</style>
