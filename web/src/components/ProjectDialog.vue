<script setup lang="ts">
import { computed } from 'vue'
import Dialog from 'primevue/dialog'
import ProjectCard from '@/components/ProjectCard.vue'
import type { ReadonlyProject } from '@/composables/useProjects'

/**
 * One project, in full, over the listing. The card does the layout — this is the
 * box it sits in: which project, and whether the box is open at all.
 *
 * The project itself is the state. There is one dialog for the whole listing
 * rather than one per card, so `null` is what closed means.
 */
const props = defineProps<{ project: ReadonlyProject | null }>()

const emit = defineEmits<{ close: [] }>()

/** Dialog wants a boolean it can also unset itself, from the mask or Escape. */
const visible = computed({
  get: () => props.project !== null,
  set: (open: boolean) => {
    if (!open) {
      emit('close')
    }
  },
})
</script>

<template>
  <Dialog
    v-model:visible="visible"
    class="project-dialog"
    modal
    dismissable-mask
    :draggable="false"
    :style="{ width: 'min(64rem, 92vw)' }"
  >
    <template #header>
      <h2 class="heading">
        <span class="year muted">{{ project?.year }}</span>
        {{ project?.title }}
      </h2>
    </template>

    <!-- Dialog focuses the first [autofocus] in a slot when it opens, and falls
         back to its close button — asking for focus-visible either way, so the
         close button opened wearing a focus ring. Focusing the body instead is
         the usual thing for a dialog anyway: Escape and Tab work from here. -->
    <div class="body" tabindex="-1" autofocus>
      <ProjectCard v-if="project" :project="project" view="full" />
    </div>
  </Dialog>
</template>

<style scoped>
.heading {
  display: flex;
  align-items: baseline;
  gap: 0.6rem;
  font-size: 1.125rem;
  font-weight: 600;
}

.year {
  font-family: var(--font-mono);
  font-weight: 400;
}

/* Only a place for focus to land, not a control: no ring around the whole card. */
.body:focus,
.body:focus-visible {
  outline: none;
}
</style>
