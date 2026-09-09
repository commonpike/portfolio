<script setup lang="ts">
import { useRoute, type LocationQueryRaw, type RouteLocationRaw } from 'vue-router'

/**
 * Pages as 00, 01, 02 — zero-based and zero-padded. Nothing is shown while
 * everything fits on one page.
 *
 * Each is a link to a URL that page can be reached at, which is what makes the
 * pages reachable at all: a crawler follows them, and so do middle-click and
 * "open in a new tab".
 */
defineProps<{ page: number; pageCount: number }>()

const route = useRoute()

/**
 * The current URL with its page changed, so whatever filters are set survive
 * paging. Page zero drops the parameter instead of writing ?page=0: the first
 * page of the listing is the listing, and it should have one address.
 */
function to(index: number): RouteLocationRaw {
  const query: LocationQueryRaw = { ...route.query }

  if (index === 0) {
    delete query.page
  } else {
    query.page = String(index)
  }

  return { query }
}

/** 0 -> "00", 12 -> "12". Three digits only once there are a hundred pages. */
function padded(index: number): string {
  return String(index).padStart(2, '0')
}
</script>

<template>
  <nav v-if="pageCount > 1" class="pager" aria-label="Pages of projects">
    <RouterLink
      v-for="index in pageCount"
      :key="index"
      :to="to(index - 1)"
      class="page-number"
      :class="{ current: page === index - 1 }"
      :aria-current="page === index - 1 ? 'page' : undefined"
    >
      {{ padded(index - 1) }}
    </RouterLink>
  </nav>
</template>

<style scoped>
.pager {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

/* Anchors, so the underline and the link colour are turned off: these read as a
   row of numbers, not as prose to click through. */
.page-number {
  padding: 0.25rem 0.5rem;
  border: 1px solid transparent;
  border-radius: var(--p-content-border-radius, 0.5rem);
  background: none;
  color: var(--p-text-muted-color);
  text-decoration: none;
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
