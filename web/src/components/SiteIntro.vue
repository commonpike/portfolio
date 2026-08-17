<script setup lang="ts">
import { useRoute } from 'vue-router'
import PageTitle from '@/components/PageTitle.vue'

/**
 * A page's heading and its intro copy. The heading is the title from the router,
 * so a page never restates its own name; the copy is passed in, because every
 * page has its own and they are only identical while the text is lorem ipsum.
 */
const route = useRoute()
</script>

<template>
  <section class="intro">
    <h1 class="heading"><PageTitle :title="route.meta.title" /></h1>

    <slot />
  </section>
</template>

<style scoped>
.intro {
  padding-block: clamp(3rem, 10vh, 7rem) clamp(2rem, 6vh, 4rem);
}

.heading {
  font-size: clamp(2.75rem, 11vw, 8rem);
  font-weight: 400;
  letter-spacing: -0.04em;
}
</style>
