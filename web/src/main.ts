import { createApp } from 'vue'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'

import '@fontsource-variable/inter'
import '@fontsource-variable/space-grotesk'
import 'primeicons/primeicons.css'
import '@/assets/main.css'

import App from '@/App.vue'
import { config } from '@/config'
import { DARK_CLASS } from '@/composables/useTheme'

createApp(App)
  .use(PrimeVue, {
    // PrimeVue 5 is licensed software and asks for a key; ours is in .env, which
    // means it is also in the built bundle — unavoidable for a browser library.
    license: config.primevueLicense,
    theme: {
      preset: Aura,
      // Dark mode follows this class rather than the system, so the switch in the
      // header decides. useTheme() puts it on <html>.
      options: { darkModeSelector: `.${DARK_CLASS}` },
    },
  })
  .mount('#app')
