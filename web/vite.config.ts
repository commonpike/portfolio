import { fileURLToPath, URL } from 'node:url'

import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig(({ mode }) => ({
  /**
   * Where the site is hosted: "/" at a domain's root, "/portfolio/" in a
   * subdirectory. Every URL the build emits is written against this — the script
   * and style tags in index.html, the favicon, anything from public/ — and Vite
   * hands it to the app as import.meta.env.BASE_URL, which is what the router
   * uses for its own base. So this one setting places the whole site.
   *
   * Read with loadEnv rather than import.meta.env: this file runs in Node, before
   * there is a bundle to substitute anything into. Empty or absent means "/",
   * which is what a build without the setting did before it existed.
   */
  base: loadEnv(mode, process.cwd(), 'VITE_').VITE_BASE_URL || '/',
  plugins: [vue(), vueDevTools()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
}))
