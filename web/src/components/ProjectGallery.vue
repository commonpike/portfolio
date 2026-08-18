<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { assetUrl } from '@/config'

/**
 * The pictures of one project, one at a time, with the paging that goes with it.
 * Only the popup uses this; the listing shows a single thumbnail.
 *
 * Paging wraps at both ends, so neither direction ever dead-ends, and there is
 * nothing to page when a project has one picture — the controls stay away.
 */
const props = defineProps<{
  /** Paths as the exporter returns them, relative to the asset root. */
  images: readonly string[]
  /** Which of them to open on: the thumbnail the card was showing. */
  start?: string
  /** What the pictures are of. */
  alt: string
}>()

const at = ref(0)

/** Paths that would not load, by index, so paging skips no beat over a gap. */
const broken = ref(new Set<number>())

/**
 * Open where the card left off, and start over when the project changes: one
 * gallery serves every project the popup shows.
 */
watch(
  () => [props.images, props.start] as const,
  ([images, start]) => {
    const from = start === undefined ? -1 : images.indexOf(start)

    at.value = from < 0 ? 0 : from
    broken.value = new Set()
  },
  { immediate: true },
)

const count = computed(() => props.images.length)
const current = computed(() => assetUrl(props.images[at.value] ?? ''))
const failed = computed(() => broken.value.has(at.value))

/**
 * The shape of the picture on show, as the box's aspect-ratio, so paging between
 * pictures of different proportions resizes rather than jumps.
 *
 * It is set when a picture loads, not when it is asked for: until then the box
 * keeps the shape of the one before, and the new picture sits inside it — better
 * than collapsing to nothing and growing back. 4:3 is only the opening guess.
 */
const shape = ref('4 / 3')

function measure(event: Event): void {
  const image = event.target as HTMLImageElement

  if (image.naturalWidth > 0 && image.naturalHeight > 0) {
    shape.value = `${image.naturalWidth} / ${image.naturalHeight}`
  }
}

/** Forwards or back, around the ends. */
function step(by: number): void {
  at.value = (at.value + by + count.value) % count.value
}

function fail(): void {
  broken.value = new Set(broken.value).add(at.value)
}

/**
 * The arrow keys page too. On the window rather than on an element of ours: the
 * popup gives focus to its body, so this works without tabbing to a button
 * first, and the gallery only ever exists inside that popup.
 */
function onKey(event: KeyboardEvent): void {
  if (count.value < 2 || event.altKey || event.ctrlKey || event.metaKey) {
    return
  }

  if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
    event.preventDefault()
    step(event.key === 'ArrowLeft' ? -1 : 1)
  }
}

onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))
</script>

<template>
  <div class="gallery" :class="{ empty: failed }" :style="{ '--shape': shape }">
    <img v-if="!failed" :src="current" :alt="alt" @load="measure" @error="fail" />
    <i v-else class="pi pi-image placeholder" aria-hidden="true" />

    <template v-if="count > 1">
      <button type="button" class="arrow back" aria-label="Previous picture" @click="step(-1)">
        <i class="pi pi-chevron-left" aria-hidden="true" />
      </button>

      <button type="button" class="arrow forth" aria-label="Next picture" @click="step(1)">
        <i class="pi pi-chevron-right" aria-hidden="true" />
      </button>

      <button
        type="button"
        class="counter"
        :aria-label="`Next picture (${at + 1} of ${count})`"
        @click="step(1)"
      >
        {{ at + 1 }} / {{ count }}
      </button>
    </template>
  </div>
</template>

<style scoped>
/* The box takes the shape of the picture in it, which is what makes the height
   transition possible: an explicit ratio can be animated to the next one, where a
   height of auto could not. */
.gallery {
  position: relative;
  width: 100%;
  aspect-ratio: var(--shape, 4 / 3);
  transition: aspect-ratio 0.3s ease;
}

/* The picture fits whatever the box is mid-transition, so it scales with it
   instead of being cropped or overflowing. */
.gallery img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: top left;
}

/* Nothing loaded: the same outlined frame an empty thumbnail gets, so a broken
   picture does not collapse the popup's first column to nothing. */
.gallery.empty {
  display: flex;
  aspect-ratio: 4 / 3;
  border: 1px solid var(--p-content-border-color);
  border-radius: var(--p-content-border-radius, 0.75rem);
}

@media (prefers-reduced-motion: reduce) {
  .gallery {
    transition: none;
  }
}

.placeholder {
  margin: auto;
  font-size: 1.5rem;
  color: var(--p-text-muted-color);
  opacity: 0.4;
}

/* The controls sit over the picture rather than beside it, so the layout does not
   shift as you page.

   The counter is the one that is always there — it says how many pictures there
   are, which is what invites paging in the first place, and it pages forward when
   clicked. That leaves the arrows free to be a mouse affordance: hovering the
   picture reveals them, leaving hides them again, and where there is no hover to
   reveal them with they are not there at all, so nothing invisible can be tapped
   by accident. */
.arrow {
  position: absolute;
  top: 50%;
  translate: 0 -50%;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border: none;
  border-radius: 999px;
  background: color-mix(in srgb, var(--p-content-background) 70%, transparent);
  color: var(--p-text-color);
  cursor: pointer;
  opacity: 0;
  transition:
    opacity 0.15s ease,
    background-color 0.15s ease;
}

.back {
  left: 0.5rem;
}

.forth {
  right: 0.5rem;
}

.gallery:hover .arrow,
.arrow:focus-visible {
  opacity: 1;
  background: var(--p-content-background);
}

.arrow:focus-visible {
  outline: 2px solid var(--p-primary-color);
  outline-offset: 2px;
}

@media (hover: none) {
  .arrow {
    display: none;
  }
}

.counter {
  position: absolute;
  right: 0.5rem;
  bottom: 0.5rem;
  margin: 0;
  padding: 0.1rem 0.5rem;
  border: none;
  border-radius: 999px;
  background: color-mix(in srgb, var(--p-content-background) 70%, transparent);
  color: var(--p-text-muted-color);
  font-family: var(--font-mono);
  font-size: 0.75rem;
  line-height: 1.5;
  cursor: pointer;
  transition:
    color 0.15s ease,
    background-color 0.15s ease;
}

.counter:hover {
  background: var(--p-content-background);
  color: var(--p-text-color);
}

.counter:focus-visible {
  outline: 2px solid var(--p-primary-color);
  outline-offset: 2px;
}
</style>
