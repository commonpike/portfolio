<script setup lang="ts">
import Button from 'primevue/button'
import { RouterLink } from 'vue-router'
import { pages } from '@/router'
import PageTitle from '@/components/PageTitle.vue'
import { useTheme } from '@/composables/useTheme'

const { scheme, toggle } = useTheme()
</script>

<template>
  <header class="site-header">
    <div class="page bar">
      <nav class="nav" aria-label="Pages">
        <RouterLink v-for="page in pages" :key="page.path" :to="page.path" class="page-link">
          <PageTitle :title="page.meta!.title" />
        </RouterLink>
      </nav>

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

.nav {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 0.5rem 1.25rem;
  font-size: 0.9375rem;
  letter-spacing: -0.01em;
}

.page-link {
  color: var(--p-text-muted-color);
  text-decoration: none;
  padding-bottom: 0.15em;
  border-bottom: 2px solid transparent;
  transition:
    color 0.15s ease,
    border-color 0.15s ease;
}

.page-link:hover {
  color: var(--p-text-color);
}

/* vue-router marks the current page; exact, so / is not active everywhere. */
.page-link.router-link-exact-active {
  color: var(--p-text-color);
  border-bottom-color: var(--p-primary-color);
}
</style>
