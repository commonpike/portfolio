<script setup lang="ts">
import { RouterView } from 'vue-router'
import SiteHeader from '@/components/SiteHeader.vue'
</script>

<template>
  <SiteHeader />

  <main class="page">
    <RouterView />
  </main>
</template>
