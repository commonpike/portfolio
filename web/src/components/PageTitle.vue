<script setup lang="ts">
import { computed } from 'vue'

/**
 * A page title with its :: in the primary colour — "pike::portfolio",
 * "about::this". Inline spans only, so the caller decides the size and weight:
 * the header uses it small, a page heading uses it large.
 */
const props = defineProps<{ title: string }>()

const parts = computed(() => {
  const at = props.title.indexOf('::')

  if (at === -1) {
    return { before: props.title, colons: false, after: '' }
  }

  return { before: props.title.slice(0, at), colons: true, after: props.title.slice(at + 2) }
})
</script>

<!-- prettier-ignore -->
<template>
  <span class="title">{{ parts.before }}<span v-if="parts.colons" class="colons">::</span>{{ parts.after }}</span>
</template>

<style scoped>
.title {
  font-family: var(--font-display);
}

.colons {
  color: var(--p-primary-color);
}
</style>
