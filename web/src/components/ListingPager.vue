<script setup lang="ts">
/**
 * Pages as 00, 01, 02 — zero-based and zero-padded. Nothing is shown while
 * everything fits on one page.
 */
defineProps<{ pageCount: number }>()

const page = defineModel<number>({ required: true })

/** 0 -> "00", 12 -> "12". Three digits only once there are a hundred pages. */
function padded(index: number): string {
  return String(index).padStart(2, '0')
}
</script>

<template>
  <nav v-if="pageCount > 1" class="pager" aria-label="Pages of projects">
    <button
      v-for="index in pageCount"
      :key="index"
      type="button"
      class="page-number"
      :class="{ current: page === index - 1 }"
      :aria-current="page === index - 1 ? 'page' : undefined"
      @click="page = index - 1"
    >
      {{ padded(index - 1) }}
    </button>
  </nav>
</template>

<style scoped>
.pager {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.page-number {
  padding: 0.25rem 0.5rem;
  border: 1px solid transparent;
  border-radius: var(--p-content-border-radius, 0.5rem);
  background: none;
  color: var(--p-text-muted-color);
  font-family: var(--font-mono);
  font-size: 0.8125rem;
  line-height: 1.2;
  cursor: pointer;
  transition:
    color 0.15s ease,
    background-color 0.15s ease,
    border-color 0.15s ease;
}

.page-number:hover {
  color: var(--p-text-color);
  background: var(--p-content-hover-background);
}

.page-number:focus-visible {
  outline: 2px solid var(--p-primary-color);
  outline-offset: 1px;
}

.current {
  color: var(--p-primary-contrast-color);
  background: var(--p-primary-color);
  border-color: var(--p-primary-color);
}

.current:hover {
  color: var(--p-primary-contrast-color);
  background: var(--p-primary-color);
}
</style>
