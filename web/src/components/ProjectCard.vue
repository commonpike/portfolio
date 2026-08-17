<script setup lang="ts">
import { computed, ref } from 'vue'
import { assetUrl } from '@/config'
import type { ReadonlyProject } from '@/composables/useProjects'

/**
 * One project. The markup is the same in both views — grid hides what it does not
 * show and stacks what it does, list puts the thumbnail beside the text — so
 * switching costs nothing and an unfolded description stays unfolded.
 */
const props = defineProps<{ project: ReadonlyProject; view: 'grid' | 'list' }>()

/** Where a folded description is cut. */
const CAP = 64

/** Credits not worth naming: the portfolio is his. */
const UNCREDITED = ['pike']

const expanded = ref(false)
const missing = ref(false)

const preview = computed(() => assetUrl(props.project.preview))

const foldable = computed(() => props.project.description.length > CAP)

/** The first CAP characters, back to the last word so nothing breaks mid-word. */
const folded = computed(() => {
  const cut = props.project.description.slice(0, CAP)
  const space = cut.lastIndexOf(' ')

  return (space > CAP / 2 ? cut.slice(0, space) : cut).trimEnd()
})

/**
 * "icw:" — who else worked on it: design, programming, production and content
 * flattened into one list, with pike himself and any repeat of a name dropped.
 */
const icw = computed(() => {
  const seen = new Set<string>()

  return [
    props.project.design,
    props.project.programming,
    props.project.production,
    props.project.content,
  ]
    .flatMap((field) => field.split(','))
    .map((name) => name.trim())
    .filter((name) => name !== '' && !UNCREDITED.includes(name.toLowerCase()))
    .filter((name) => {
      const key = name.toLowerCase()
      if (seen.has(key)) {
        return false
      }
      seen.add(key)

      return true
    })
})

/** The link as something to read, rather than as a URL. */
const linkLabel = computed(() => props.project.link.replace(/^https?:\/\//, '').replace(/\/$/, ''))
</script>

<template>
  <article class="card" :class="view">
    <div class="thumb">
      <img
        v-if="preview !== '' && !missing"
        :src="preview"
        :alt="project.title"
        loading="lazy"
        @error="missing = true"
      />
      <i v-else class="pi pi-image placeholder" aria-hidden="true" />
    </div>

    <div class="content">
      <div class="upper">
        <h3 class="title">{{ project.title }}</h3>

        <a
          v-if="project.link !== ''"
          class="link"
          :href="project.link"
          target="_blank"
          rel="noopener noreferrer"
          >{{ linkLabel }}</a
        >

        <p v-if="project.owner !== ''" class="owner muted">{{ project.owner }}</p>

        <p v-if="project.description !== ''" class="description">
          {{ expanded || !foldable ? project.description : folded
          }}<template v-if="foldable">
            <button type="button" class="fold" @click="expanded = !expanded">
              {{ expanded ? 'less..' : 'more..' }}
            </button>
          </template>
        </p>
      </div>

      <footer class="foot">
        <span v-if="project.type !== ''" class="pill type">{{ project.type }}</span>

        <span v-for="technology in project.technologies" :key="technology" class="pill">
          {{ technology }}
        </span>

        <span v-for="role in project.roles" :key="role" class="pill role">{{ role }}</span>

        <span v-if="icw.length > 0" class="icw muted">icw: {{ icw.join(', ') }}</span>
      </footer>
    </div>
  </article>
</template>

<style scoped>
/* The thumbnail sits in a 4:3 box whatever shape it is; only its alignment
   differs between the views. */
.thumb {
  display: flex;
  align-items: flex-start;
  aspect-ratio: 4 / 3;
  overflow: hidden;
}

.thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.placeholder {
  margin: auto;
  font-size: 1.5rem;
  color: var(--p-text-muted-color);
  opacity: 0.4;
}

.title {
  font-size: 1.0625rem;
  font-weight: 600;
  letter-spacing: -0.01em;
}

.link,
.owner {
  display: block;
  font-size: 0.8125rem;
}

.link {
  margin-top: 0.35rem;
  font-family: var(--font-mono);
  text-decoration: none;
}

.link:hover {
  text-decoration: underline;
}

.owner {
  margin: 0.2rem 0 0;
}

.description {
  margin: 0.75rem 0 0;
  max-width: 62ch;
}

.fold {
  padding: 0;
  border: none;
  background: none;
  color: var(--p-primary-color);
  font: inherit;
  cursor: pointer;
}

.fold:hover {
  text-decoration: underline;
}

.foot {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-top: 1rem;
}

.pill {
  padding: 0.15em 0.6em;
  border: 1px solid var(--p-content-border-color);
  border-radius: 999px;
  background: var(--p-surface-100);
  font-size: 0.75rem;
  line-height: 1.5;
  white-space: nowrap;
}

:global(.app-dark) .pill {
  background: var(--p-surface-800);
}

.pill.type {
  border-color: transparent;
  background: color-mix(in srgb, var(--p-primary-color) 18%, transparent);
  color: var(--p-primary-color);
  font-weight: 500;
}

.pill.role {
  background: none;
  color: var(--p-text-muted-color);
}

.icw {
  font-size: 0.75rem;
  margin-left: 0.35rem;
}

/* Thumbnail view: the picture, then just enough to name it. */
.card.grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.card.grid .thumb img {
  object-position: top center;
}

.card.grid .description,
.card.grid .foot {
  display: none;
}

/* Detail view: picture on the left, text on the right, footer at its foot. */
.card.list {
  display: grid;
  grid-template-columns: minmax(0, 15rem) minmax(0, 1fr);
  gap: 1.75rem;
}

.card.list .thumb img {
  object-position: top left;
}

.card.list .content {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 1rem;
}

@media (max-width: 48rem) {
  .card.list {
    grid-template-columns: minmax(0, 1fr);
    gap: 1rem;
  }

  .card.list .thumb {
    max-width: 15rem;
  }
}
</style>
