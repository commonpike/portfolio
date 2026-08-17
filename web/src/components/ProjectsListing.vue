<script setup lang="ts">
import { computed, onMounted } from 'vue'
import Button from 'primevue/button'
import { config } from '@/config'
import { useProjects } from '@/composables/useProjects'

const { projects, error, loading, load, reload } = useProjects()

// Nothing is fetched until this runs; calling it twice is harmless.
onMounted(() => void load())

/** Placeholder for the listing: the JSON verbatim until the real one lands. */
const blurb = computed(() => JSON.stringify(projects.value, null, 2))
</script>

<template>
  <section class="listing">
    <header class="heading">
      <h2>Projects</h2>
      <p class="muted count">
        <template v-if="loading">Loading…</template>
        <template v-else-if="error">—</template>
        <template v-else>{{ projects.length }} from {{ config.jsonUrl }}</template>
      </p>
    </header>

    <div v-if="error" class="panel notice">
      <p class="failed">{{ error }}</p>
      <Button label="Try again" icon="pi pi-refresh" size="small" @click="reload()" />
    </div>

    <pre v-else-if="!loading" class="panel dump">{{ blurb }}</pre>

    <div v-else class="panel notice muted">Fetching the portfolio…</div>
  </section>
</template>

<style scoped>
.listing {
  padding-bottom: clamp(3rem, 10vh, 6rem);
}

.heading {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
}

.heading h2 {
  font-size: clamp(1.75rem, 4vw, 2.5rem);
}

.count {
  margin: 0;
  font-family: var(--font-mono);
  font-size: 0.8125rem;
}

.notice {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  padding: 1.25rem 1.5rem;
}

.failed {
  margin: 0;
  color: var(--p-red-500, #ef4444);
}

.dump {
  max-height: 32rem;
  overflow: auto;
  margin: 0;
  padding: 1.25rem 1.5rem;
  background: var(--p-surface-50);
  font-family: var(--font-mono);
  font-size: 0.8125rem;
  line-height: 1.5;
  tab-size: 2;
}

:global(.app-dark) .dump {
  background: var(--p-surface-900);
}
</style>
