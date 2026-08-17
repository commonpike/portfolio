<script setup lang="ts" generic="T">
import { useId } from 'vue'
import Select from 'primevue/select'

/** One labelled control in the listing's bars: "Type [ all ▾ ]". */
defineProps<{
  label: string
  options: { label: string; value: T }[]
}>()

const model = defineModel<T>({ required: true })

const id = useId()
</script>

<template>
  <div class="field">
    <label class="label" :for="id">{{ label }}</label>

    <Select
      v-model="model"
      :input-id="id"
      :options="options"
      option-label="label"
      option-value="value"
      size="small"
    />
  </div>
</template>

<style scoped>
.field {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.label {
  font-family: var(--font-display);
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--p-text-muted-color);
}
</style>
