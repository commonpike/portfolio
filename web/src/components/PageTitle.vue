<script setup lang="ts">
import { computed } from 'vue'
import { useFlicker } from '@/composables/useFlicker'

/**
 * A page title with its :: in the primary colour — "pike::portfolio",
 * "about::this". Inline spans only, so the caller decides the size and weight:
 * the header uses it small, a page heading uses it large.
 */
const props = defineProps<{ title: string }>()

/** Every title's colons take part in the page-wide flicker. */
const out = useFlicker()

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
  <span class="title">{{ parts.before }}<span v-if="parts.colons" class="colons" :class="{ out }">::</span>{{ parts.after }}</span>
</template>

<style scoped>
.title {
  font-family: var(--font-display);
}

.colons {
  color: var(--p-primary-color);
  /* Only on the way back: the colon drops out at once and fades in again, which
     is what makes it read as a flicker rather than as a colour that changed. */
  transition: color 0.3s ease;
}

/* The colon goes to nothing rather than to a colour — whatever is behind it shows
   through, so light and dark need no separate case the way a white flash did. */
.colons.out {
  color: transparent;
  transition: none;
}
</style>
