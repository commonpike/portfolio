<script setup lang="ts">
import SiteIntro from '@/components/SiteIntro.vue'
import PageContent from '@/components/PageContent.vue'
import ProjectsListing from '@/components/ProjectsListing.vue'
</script>

<template>
  <SiteIntro>
    <PageContent name="portfolio" />
  </SiteIntro>

  <ProjectsListing />
</template>
