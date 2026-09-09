<script setup lang="ts">
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue'
import { useRoute, useRouter, type LocationQueryRaw, type LocationQueryValue } from 'vue-router'
import Button from 'primevue/button'
import ListingSelect from '@/components/ListingSelect.vue'
import ListingPager from '@/components/ListingPager.vue'
import ProjectCard from '@/components/ProjectCard.vue'
import ProjectDialog from '@/components/ProjectDialog.vue'
import { useProjects, type ReadonlyProject } from '@/composables/useProjects'

const { projects, error, loading, load, reload } = useProjects()

// Nothing is fetched until this runs; calling it twice is harmless.
onMounted(() => void load())

/**
 * How much of the portfolio "Show" lets through. Rank defaults to 50 in the
 * assets and higher sorts first, so these are thresholds on that: a project
 * earns its way into the shorter lists with a higher rank.txt.
 */
const LEVELS = [
  { label: 'some', value: 100 },
  { label: 'most', value: 50 },
  { label: 'all', value: 0 },
]

/** Projects per page. Number, or 'all' for one page of everything. */
const LIMITS = [
  { label: '10', value: 10 as number | 'all' },
  { label: '25', value: 25 as number | 'all' },
  { label: 'all', value: 'all' as number | 'all' },
]

/**
 * What each of the four means when its parameter is absent — which is the only
 * state a crawler ever sees, since nothing links to a filtered listing.
 */
const DEFAULTS = {
  type: 'all',
  level: LEVELS[1]!.value,
  limit: LIMITS[0]!.value,
  page: 0,
}

const route = useRoute()
const router = useRouter()

/**
 * The listing's state lives in the query string, which is what gives every page
 * of it an address — the pager is a row of links to them, so a crawler reaches
 * the whole portfolio rather than the first ten projects.
 *
 * The filters are in there for the reader rather than the crawler: a filtered
 * listing is worth sharing, and the back button undoes a change. Only the pager
 * emits links, so the URLs anything can crawl are ?page=N at the default filters
 * and nothing else — the combinations stay out of an index. A value at its
 * default writes no parameter at all, so the first page of an unfiltered listing
 * is a bare /portfolio/.
 *
 * Each of them is a writable computed, which v-model takes exactly like a ref.
 */

/** The first value of a parameter, whatever shape ?type=a&type=b arrived in. */
function text(value: LocationQueryValue | LocationQueryValue[] | undefined): string {
  return (Array.isArray(value) ? value[0] : value) ?? ''
}

/** A parameter's value, or undefined when it is the default and can be left out. */
function unless<T>(value: T, fallback: T): string | undefined {
  return value === fallback ? undefined : String(value)
}

/**
 * Writes the parameters that changed and drops the ones back at their default.
 * Filters replace rather than push: a dropdown is an adjustment, and three of
 * them in a row should not be three presses of the back button. The pager pushes,
 * because moving to page two is a move.
 */
function write(changes: LocationQueryRaw, method: 'push' | 'replace' = 'replace'): void {
  const query: LocationQueryRaw = { ...route.query, ...changes }

  for (const [key, value] of Object.entries(query)) {
    if (value === undefined) {
      delete query[key]
    }
  }

  void router[method]({ query })
}

/**
 * Every reader falls back to the default rather than trusting what it finds:
 * these are values a stranger can type, and the listing is not the place to
 * discover that ?limit=-1 was possible.
 */
const type = computed({
  get: () => text(route.query.type) || DEFAULTS.type,
  /**
   * Picking a type widens "Show" to its last level, 'all'. The two filters
   * multiply, and a type holding nothing ranked above the current threshold
   * would otherwise answer with an empty listing.
   */
  set: (value: string) =>
    write({
      type: unless(value, DEFAULTS.type),
      level: value === DEFAULTS.type ? undefined : unless(LEVELS.at(-1)!.value, DEFAULTS.level),
      page: undefined,
    }),
})

const level = computed({
  get: () => LEVELS.find((o) => String(o.value) === text(route.query.level))?.value ?? DEFAULTS.level,
  set: (value: number) => write({ level: unless(value, DEFAULTS.level), page: undefined }),
})

const limit = computed({
  get: () => LIMITS.find((o) => String(o.value) === text(route.query.limit))?.value ?? DEFAULTS.limit,
  set: (value: number | 'all') => write({ limit: unless(value, DEFAULTS.limit), page: undefined }),
})

const page = computed({
  get: () => {
    const asked = Number(text(route.query.page))

    return Number.isInteger(asked) && asked > 0 ? asked : DEFAULTS.page
  },
  set: (value: number) => write({ page: unless(value, DEFAULTS.page) }, 'push'),
})

/** How a project is drawn: 'grid' as a thumbnail, 'list' in detail. Detail first. */
const view = ref<'grid' | 'list'>('list')

/**
 * The project the popup is showing, if any. One dialog serves the whole listing —
 * the cards only say which project to open, they do not each carry a dialog.
 */
const opened = ref<ReadonlyProject | null>(null)

/** The listing's own top edge — what a change of page scrolls back to, below. */
const root = useTemplateRef<HTMLElement>('root')

function toggleView(): void {
  view.value = view.value === 'grid' ? 'list' : 'grid'
}

/**
 * The types on offer, from the projects themselves rather than a list here, so a
 * type added to the assets turns up on its own. Values are as authored.
 */
const types = computed(() => {
  const found = [...new Set(projects.value.map((project) => project.type).filter(Boolean))].sort()

  return [{ label: 'all', value: 'all' }, ...found.map((name) => ({ label: name, value: name }))]
})

/** Everything the filters let through, in the order the library sorted it. */
const selected = computed(() =>
  projects.value.filter(
    (project) =>
      (type.value === 'all' || project.type.toLowerCase() === type.value.toLowerCase()) &&
      project.rank >= level.value,
  ),
)

const perPage = computed(() =>
  limit.value === 'all' ? Math.max(selected.value.length, 1) : limit.value,
)
const pageCount = computed(() => Math.max(1, Math.ceil(selected.value.length / perPage.value)))
const from = computed(() => page.value * perPage.value)
const visible = computed(() => selected.value.slice(from.value, from.value + perPage.value))

/**
 * This page's projects under a heading per year. The exporter already sorts by
 * year descending, so a year starts wherever it changes — no regrouping, and a
 * year split across two pages simply appears on both.
 */
const years = computed(() => {
  const groups: { year: string; projects: ReadonlyProject[] }[] = []

  for (const project of visible.value) {
    const current = groups.at(-1)

    if (current?.year === project.year) {
      current.projects.push(project)
    } else {
      groups.push({ year: project.year, projects: [project] })
    }
  }

  return groups
})

/**
 * A page number out of range — ?page=99, or a filter narrowed elsewhere — comes
 * back to the last page there is. It waits for the fetch: until the projects are
 * in, every listing is one page long and a perfectly good ?page=2 would be
 * clamped away before it could be honoured.
 *
 * Replacing rather than pushing, because arriving at a page that isn't there is
 * not a move the reader made and should not be one the back button undoes.
 */
watch([pageCount, loading], ([count, busy]) => {
  if (!busy && page.value > count - 1) {
    write({ page: unless(count - 1, DEFAULTS.page) })
  }
})

/**
 * A change of page brings the head of the listing back into view. The pager at the
 * foot is what needs it — clicking 01 there leaves you at the bottom of a page you
 * have not seen — but this watches the page rather than the click, so the top pager
 * and a filter that resets the page do the same thing.
 *
 * The listing's top, not the document's: that puts the filters and the first
 * project on screen, where scrolling to 0 would show the page heading instead.
 * Smooth unless the reader asked for less motion, the way the gallery does it.
 */
watch(page, () => {
  root.value?.scrollIntoView({
    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
    block: 'start',
  })
})
</script>

<template>
  <section ref="root" class="listing">
    <header class="bar">
      <div class="filters">
        <ListingSelect v-model="type" label="Type" :options="types" />
        <ListingSelect v-model="level" label="Show" :options="LEVELS" />
        <ListingSelect v-model="limit" label="Limit" :options="LIMITS" />

        <button
          type="button"
          class="view-toggle"
          :aria-label="view === 'grid' ? 'Switch to detail view' : 'Switch to thumbnail view'"
          @click="toggleView()"
        >
          <i :class="view === 'grid' ? 'pi pi-bars' : 'pi pi-th-large'" aria-hidden="true" />
          {{ view === 'grid' ? 'detail' : 'thumbnails' }}
        </button>
      </div>

      <ListingPager :page="page" :page-count="pageCount" />
    </header>

    <p class="muted tally">
      <template v-if="loading">Loading…</template>
      <template v-else-if="error">—</template>
      <template v-else-if="selected.length === 0">nothing matches these filters</template>
      <template v-else>
        {{ from + 1 }}–{{ from + visible.length }} of {{ selected.length }}
        <template v-if="selected.length !== projects.length"
          >({{ projects.length }} in all)</template
        >
      </template>
    </p>

    <div v-if="error" class="panel notice">
      <p class="failed">{{ error }}</p>
      <Button label="Try again" icon="pi pi-refresh" size="small" @click="reload()" />
    </div>

    <div v-else-if="loading" class="panel notice muted">Fetching the portfolio…</div>

    <div v-for="group in years" v-else :key="group.year" class="year">
      <h2 class="year-heading">{{ group.year }}</h2>

      <div class="projects" :class="view">
        <ProjectCard
          v-for="project in group.projects"
          :key="project.path"
          :project="project"
          :view="view"
          @open="opened = project"
        />
      </div>
    </div>

    <footer class="bar bottom">
      <ListingSelect v-model="limit" label="Limit" :options="LIMITS" />
      <ListingPager :page="page" :page-count="pageCount" />
    </footer>

    <ProjectDialog :project="opened" @close="opened = null" />
  </section>
</template>

<style scoped>
.listing {
  padding-bottom: clamp(3rem, 10vh, 6rem);
  /* Paging scrolls this edge to the top of the viewport, where the site header is
     sticky and would cover the filter bar. Its own bar is 4rem, plus air. */
  scroll-margin-top: 5rem;
}

.bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem 1.5rem;
  flex-wrap: wrap;
  padding-block: 0.75rem;
  border-block: 1px solid var(--p-content-border-color);
}

.bottom {
  border-top: none;
  margin-top: 2rem;
}

.filters {
  display: flex;
  align-items: center;
  gap: 1rem 1.5rem;
  flex-wrap: wrap;
}

.view-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0;
  border: none;
  background: none;
  color: var(--p-text-muted-color);
  font-family: var(--font-display);
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: color 0.15s ease;
}

.view-toggle:hover {
  color: var(--p-text-color);
}

.view-toggle:focus-visible {
  outline: 2px solid var(--p-primary-color);
  outline-offset: 3px;
  border-radius: 2px;
}

.view-toggle i {
  font-size: 0.875rem;
  color: var(--p-primary-color);
}

.tally {
  margin: 0.75rem 0 2rem;
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

/* Years are divided too, by a rule a step stronger than the one between projects
   — mixed up from the text colour rather than widened, so it reads as the same
   kind of line. It sits on the year wrapper, so both views get it. */
.year + .year {
  --year-space: clamp(1.75rem, 4vh, 2.75rem);
  margin-top: var(--year-space);
  border-top: 1px solid color-mix(in srgb, var(--p-text-color) 25%, var(--p-content-border-color));
  padding-top: var(--year-space);
}

.year-heading {
  font-size: clamp(1.75rem, 4vw, 2.75rem);
  font-weight: 400;
  letter-spacing: -0.03em;
  color: var(--p-text-muted-color);
  margin-bottom: 1.25rem;
}

.projects.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr));
  gap: 2rem 1.5rem;
}

/* Detail view sets its projects apart with a thin rule between them, with equal
   air either side — one value tunes both. The grid needs none: its thumbnails
   already read as separate things. */
.projects.list {
  --rule-space: clamp(1.25rem, 3vh, 2rem);
  display: flex;
  flex-direction: column;
  gap: var(--rule-space);
}

/* The child's root element carries this scope too, so no class of its own is
   needed here. Only *between* projects: no rule above the first or below the
   last, where the year headings already do the dividing. */
.projects.list > * + * {
  border-top: 1px solid var(--p-content-border-color);
  padding-top: var(--rule-space);
}
</style>
