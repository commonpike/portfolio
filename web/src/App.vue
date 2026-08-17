<script setup lang="ts">
import SiteHeader from '@/components/SiteHeader.vue'
import SiteIntro from '@/components/SiteIntro.vue'
import ProjectsListing from '@/components/ProjectsListing.vue'
</script>

<template>
  <SiteHeader />

  <main class="page">
    <SiteIntro />
    <ProjectsListing />
  </main>
</template>
