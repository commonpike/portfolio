<script setup lang="ts">
import { computed, ref } from 'vue'
import ProjectGallery from '@/components/ProjectGallery.vue'
import { assetUrl } from '@/config'
import type { ReadonlyProject } from '@/composables/useProjects'

/**
 * One project. The markup is the same in all three views — grid hides what it
 * does not show and stacks what it does, list puts the thumbnail beside the text,
 * full is list with the picture given most of the width — so switching costs
 * nothing and an unfolded description stays unfolded.
 */
const props = defineProps<{ project: ReadonlyProject; view: 'grid' | 'list' | 'full' }>()

/** Asked to be opened in the popup. What that means is the listing's business. */
const emit = defineEmits<{ open: [] }>()

/** Where a folded description is cut. */
const CAP = 256

/** Credits not worth naming: the portfolio is his. */
const UNCREDITED = ['pike']

const expanded = ref(false)
const missing = ref(false)

const preview = computed(() => assetUrl(props.project.preview))

/** Whether there is a picture to show: one is named, and it loaded. */
const shown = computed(() => preview.value !== '' && !missing.value)

/**
 * In the popup the pictures are paged through rather than shown one at a time, so
 * the gallery takes the thumbnail's place — and with it the loading and the empty
 * frame, which is why neither `shown` nor `.empty` applies then.
 */
const gallery = computed(() => props.view === 'full' && props.project.images.length > 0)

/**
 * Whether the description is shown cut. Never in the popup: that view exists to
 * show the project in full, and it has the room, so there is nothing there for a
 * `more..` to reveal.
 */
const foldable = computed(() => props.view !== 'full' && props.project.description.length > CAP)

/** The first CAP characters, back to the last word so nothing breaks mid-word. */
const folded = computed(() => {
  const cut = props.project.description.slice(0, CAP)
  const space = cut.lastIndexOf(' ')

  return (space > CAP / 2 ? cut.slice(0, space) : cut).trimEnd()
})

/**
 * Who the project was for and who produced it, on the line under the title:
 * "owner / production", or whichever of the two the assets name. Production is
 * credited here rather than in the icw line below.
 */
const attribution = computed(() =>
  [props.project.owner, props.project.production].filter((field) => field !== '').join(' / '),
)

/**
 * "icw:" — who else worked on it: design, programming and content flattened into
 * one list, with pike himself and any repeat of a name dropped.
 */
const icw = computed(() => {
  const seen = new Set<string>()

  return [props.project.design, props.project.programming, props.project.content]
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

/**
 * What opens the popup: the whole card in the grid, where there is little else to
 * click, but only the thumbnail and the title in detail view, where the text is
 * long enough to want selecting and the link is worth clicking on its own. The
 * full view is the popup, so nothing in it opens anything.
 */
const wholeCardOpens = computed(() => props.view === 'grid')
const partsOpen = computed(() => props.view === 'list')

/**
 * What makes an element open the popup: a button in all but name, so that a
 * keyboard reaches it too. Spread onto whichever elements the view makes
 * clickable, together with the two handlers below.
 */
const opener = { role: 'button', tabindex: 0 }

function open(): void {
  emit('open')
}

/** Enter and Space, the keys a real button would answer to. */
function openOnKey(event: KeyboardEvent): void {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    emit('open')
  }
}
</script>

<template>
  <article
    class="card"
    :class="[view, { opens: wholeCardOpens }]"
    v-bind="wholeCardOpens ? { ...opener, 'aria-label': `Open ${project.title}` } : {}"
    @click="wholeCardOpens && open()"
    @keydown="wholeCardOpens && openOnKey($event)"
  >
    <div
      class="thumb"
      :class="{ empty: !gallery && !shown, opens: partsOpen }"
      v-bind="partsOpen ? { ...opener, 'aria-label': `Open ${project.title}` } : {}"
      @click="partsOpen && open()"
      @keydown="partsOpen && openOnKey($event)"
    >
      <ProjectGallery
        v-if="gallery"
        :images="project.images"
        :start="project.preview"
        :alt="project.title"
      />

      <img
        v-else-if="shown"
        :src="preview"
        :alt="project.title"
        loading="lazy"
        @error="missing = true"
      />

      <i v-else class="pi pi-image placeholder" aria-hidden="true" />
    </div>

    <div class="content">
      <div class="upper">
        <h3
          class="title"
          :class="{ opens: partsOpen }"
          v-bind="partsOpen ? opener : {}"
          @click="partsOpen && open()"
          @keydown="partsOpen && openOnKey($event)"
        >
          {{ project.title }}
        </h3>

        <a
          v-if="project.link !== ''"
          class="link"
          :href="project.link"
          target="_blank"
          rel="noopener noreferrer"
          @click.stop
          >{{ linkLabel }}</a
        >

        <p v-if="attribution !== ''" class="attribution muted">{{ attribution }}</p>

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
        <p v-if="icw.length > 0" class="icw muted">icw: {{ icw.join(', ') }}</p>

        <div class="pills">
          <span v-if="project.type !== ''" class="pill type">{{ project.type }}</span>

          <span
            v-for="technology in project.technologies"
            :key="technology"
            class="pill technology"
          >
            {{ technology }}
          </span>

          <span v-for="role in project.roles" :key="role" class="pill role">{{ role }}</span>
        </div>
      </footer>
    </div>
  </article>
</template>

<style scoped>
/* Whatever opens the popup says so, and shows a focus ring: these are buttons in
   all but name, and the view decides which elements wear the class. */
.opens {
  cursor: pointer;
}

.opens:focus-visible {
  outline: 2px solid var(--p-primary-color);
  outline-offset: 3px;
  border-radius: 2px;
}

.title.opens:hover {
  color: var(--p-primary-color);
}

/* Where the 4:3 box comes from differs per view, below: the grid wants every
   thumbnail the same shape, detail wants the picture's own. */
.thumb {
  display: flex;
  align-items: flex-start;
  overflow: hidden;
}

/* No picture, or one that would not load: outline the box it would have filled,
   so the placeholder reads as an empty frame rather than a gap in the card. */
.thumb.empty {
  border: 1px solid var(--p-content-border-color);
  border-radius: var(--p-content-border-radius, 0.75rem);
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
.attribution {
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

.attribution {
  margin: 0.2rem 0 0;
}

.description {
  margin: 0.75rem 0 0;
  max-width: 62ch;
}

/* The template puts no whitespace between the description and this button, so
   that a cut sentence is not followed by a stray space; the gap is here. */
.fold {
  margin-left: 0.4rem;
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

/* The credits read above the pills, as a line of their own. */
.foot {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
  margin-top: 1rem;
}

.icw {
  margin: 0;
  font-size: 0.75rem;
}

.pills {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.4rem;
}

/* Shape and type only: what a pill *is* comes from its fill below. */
.pill {
  padding: 0.15em 0.6em;
  border: 1px solid var(--p-content-border-color);
  border-radius: 999px;
  font-size: 0.75rem;
  line-height: 1.5;
  white-space: nowrap;
}

/* The three kinds are told apart by that fill: the type carries the accent, a
   technology a neutral wash of the text colour, a role nothing but the border.
   Both mixes are against tokens that flip with the theme, so unlike the surface
   shade this replaces, neither needs a dark variant. */
.pill.type {
  border-color: transparent;
  background: color-mix(in srgb, var(--p-primary-color) 18%, transparent);
  color: var(--p-primary-color);
  font-weight: 500;
}

.pill.technology {
  border-color: transparent;
  background: color-mix(in srgb, var(--p-text-color) 12%, transparent);
}

.pill.role {
  background: none;
  color: var(--p-text-muted-color);
}

/* Thumbnail view: the picture, then just enough to name it. Every thumbnail is
   the same 4:3 box whatever shape its picture is, or the grid would not line up. */
.card.grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.card.grid .thumb {
  aspect-ratio: 4 / 3;
}

.card.grid .thumb img {
  object-position: top center;
}

.card.grid .description,
.card.grid .foot {
  display: none;
}

/* Detail view: picture on the left, text on the right, footer at its foot. Popup
   view is the same arrangement with the picture given two thirds of the box —
   room for the gallery it is going to become. */
.card.list {
  display: grid;
  grid-template-columns: minmax(0, 15rem) minmax(0, 1fr);
  gap: 1.75rem;
}

.card.full {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
  gap: 1.75rem;
}

/* In both, the picture keeps its own proportions and sets the height itself, so
   the text beside it starts level with the top of the image instead of with a 4:3
   box a tall or wide picture only partly fills. */
.card.list .thumb img,
.card.full .thumb img {
  height: auto;
  object-position: top left;
}

/* With no picture there is nothing to set that height, so the empty frame falls
   back to the box it would have been. */
.card.list .thumb.empty,
.card.full .thumb.empty {
  aspect-ratio: 4 / 3;
}

.card.list .content,
.card.full .content {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 1rem;
}

/* The popup's own header names the project, so the card does not repeat it. */
.card.full .title {
  display: none;
}

@media (max-width: 48rem) {
  .card.list,
  .card.full {
    grid-template-columns: minmax(0, 1fr);
    gap: 1rem;
  }

  .card.list .thumb {
    max-width: 15rem;
  }
}
</style>
