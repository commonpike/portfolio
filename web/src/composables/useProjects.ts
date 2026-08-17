import { readonly, ref, type DeepReadonly } from 'vue'
import { config, missingSettings } from '@/config'
import type { Project } from '@/types'

/**
 * A project as consumers get it. The state below is shared, so it is handed out
 * readonly; components take this rather than Project, which props are anyway.
 */
export type ReadonlyProject = DeepReadonly<Project>

/**
 * Every project, from the JSON exporter. The whole listing is fetched once and
 * narrowed in the browser afterwards — the exporter caches it, and filtering,
 * sorting and paging are coming here rather than into the query string.
 *
 * The state lives at module level, so a second caller gets the listing the first
 * one already fetched. Nothing is requested until someone calls load(), which is
 * a no-op once it has: pass true, or call reload(), to go again.
 */

const projects = ref<Project[]>([])
const error = ref('')
const loading = ref(false)
const loaded = ref(false)

/** The request in flight, so concurrent callers share one fetch. */
let pending: Promise<void> | null = null

async function fetchProjects(): Promise<void> {
  const missing = missingSettings()

  if (missing.length > 0) {
    error.value = `Not configured: ${missing.join(', ')}. Copy web/.env.dist to web/.env.`
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await fetch(config.jsonUrl, { headers: { Accept: 'application/json' } })
    const body = await response.json()

    // The exporter reports bad input as { error: … } with a 400.
    if (!response.ok) {
      throw new Error(body?.error ?? `${response.status} ${response.statusText}`)
    }

    projects.value = body as Project[]
    loaded.value = true
  } catch (cause) {
    projects.value = []
    error.value = cause instanceof Error ? cause.message : String(cause)
  } finally {
    loading.value = false
  }
}

function load(force = false): Promise<void> {
  if (pending) {
    return pending
  }

  if (loaded.value && !force) {
    return Promise.resolve()
  }

  pending = fetchProjects().finally(() => {
    pending = null
  })

  return pending
}

export function useProjects() {
  return {
    projects: readonly(projects),
    error: readonly(error),
    loading: readonly(loading),
    loaded: readonly(loaded),
    load,
    reload: () => load(true),
  }
}
