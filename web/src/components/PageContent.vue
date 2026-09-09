<script setup lang="ts">
import { ref, watch } from 'vue'
import { contentUrl } from '@/config'

/**
 * A page's copy, fetched from public/content/<name>.html rather than written in
 * the template. The point is the live server: those files are copied into the
 * build verbatim, so a typo is fixed by editing one of them in place — no
 * rebuild, no reupload of the bundle.
 *
 * The fragment is trusted markup: it ships with the site and is served from the
 * site's own origin, which is what makes v-html the right tool here and not a
 * hole. Nothing user-supplied ever reaches it.
 */
const props = defineProps<{
  /** The file under content/, without its .html — "cv" reads content/cv.html. */
  name: string
}>()

const html = ref('')
const error = ref('')

/**
 * Revalidated on every load ("no-cache" sends the request and takes a 304 when
 * nothing changed), because a cached fragment would undo the whole point: an
 * edit on the server has to show up on the next reload.
 */
async function load(name: string): Promise<void> {
  html.value = ''
  error.value = ''

  try {
    const response = await fetch(contentUrl(name), { cache: 'no-cache' })

    if (!response.ok) {
      throw new Error(`${response.status} ${response.statusText}`)
    }

    html.value = await response.text()
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : String(cause)
  }
}

watch(() => props.name, load, { immediate: true })
</script>

<template>
  <div class="content">
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div v-if="html" v-html="html"></div>

    <p v-else-if="error" class="muted">
      This page's copy could not be loaded from <code>content/{{ name }}.html</code>: {{ error }}
    </p>
  </div>
</template>

<style scoped>
/**
 * Styles for the markup inside the fragments. They need :deep(), because v-html
 * content carries no scope attribute — and being here rather than in main.css
 * keeps them with the thing they style: whatever a content file may use.
 *
 * The rest of what a fragment can lean on (.lede, .muted, .intro p) is already
 * global in main.css.
 */

/* A mark for the service each address belongs to: flat, monochrome and a size down
   from the text, so the lines are told apart at a glance without the top of the
   page turning into a row of logos. Muted rather than in the link colour — it
   labels the line, the address is what is clicked.

   Every mark gets the same 1em box whatever the glyph's own width is, so the
   addresses start at one left edge instead of a ragged one. */
.content :deep(.icon) {
  display: inline-block;
  width: 1em;
  margin-right: 0.5em;
  font-size: 0.8em;
  text-align: center;
  color: var(--p-text-muted-color);
}

/* The drawn one is sized in em like the glyphs are, so it carries the same weight
   and adds nothing to the line's height. */
.content :deep(svg.icon) {
  height: 1em;
  fill: currentColor;
  vertical-align: -0.1em;
}

/* A block set apart as a quote — Claude's answer on about::this. */
.content :deep(.comment) {
  max-width: 60ch;
  margin-inline: 0;
  padding-inline: 1.25rem;
  border-inline-start: 2px solid var(--p-primary-color);
  color: var(--p-text-muted-color);
}
</style>
