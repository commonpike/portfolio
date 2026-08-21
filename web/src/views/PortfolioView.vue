<script setup lang="ts">
import SiteIntro from '@/components/SiteIntro.vue'
import ProjectsListing from '@/components/ProjectsListing.vue'
</script>

<template>
  <SiteIntro>
    <p class="lede">
      Keeping track of everything I did is hard.
      Projects overlap, get revived, linger on, die off.
      Once they're out of sight, I often forget to take a screenshot,
      make some notes. After all, it's online, people can
      look there - until it's gone. But now it's here, or most of it.
    </p>
  </SiteIntro>

  <ProjectsListing />
</template>
