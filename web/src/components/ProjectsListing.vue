<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
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
  { label: 'some', value: 70 },
  { label: 'most', value: 50 },
  { label: 'all', value: 0 },
]

/** Projects per page. Number, or 'all' for one page of everything. */
const LIMITS = [
  { label: '10', value: 10 as number | 'all' },
  { label: '25', value: 25 as number | 'all' },
  { label: 'all', value: 'all' as number | 'all' },
]

const type = ref('all')
const level = ref(LEVELS[1]!.value)
const limit = ref<number | 'all'>(10)
const page = ref(0)

/** How a project is drawn: 'grid' as a thumbnail, 'list' in detail. Detail first. */
const view = ref<'grid' | 'list'>('list')

/**
 * The project the popup is showing, if any. One dialog serves the whole listing —
 * the cards only say which project to open, they do not each carry a dialog.
 */
const opened = ref<ReadonlyProject | null>(null)

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
 * Picking a type widens "Show" to its last level, 'all'. The two filters
 * multiply, and a type that holds nothing ranked above the current threshold
 * would otherwise answer with an empty listing.
 */
watch(type, (value) => {
  if (value !== 'all') {
    level.value = LEVELS.at(-1)!.value
  }
})

// Narrowing the list can strand you past its end; go back to the first page.
watch([type, level, limit], () => {
  page.value = 0
})

watch(pageCount, (count) => {
  if (page.value > count - 1) {
    page.value = count - 1
  }
})
</script>

<template>
  <section class="listing">
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

      <ListingPager v-model="page" :page-count="pageCount" />
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
      <ListingPager v-model="page" :page-count="pageCount" />
    </footer>

    <ProjectDialog :project="opened" @close="opened = null" />
  </section>
</template>

<style scoped>
.listing {
  padding-bottom: clamp(3rem, 10vh, 6rem);
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
