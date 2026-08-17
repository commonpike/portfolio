import { ref, watchEffect } from 'vue'

/**
 * Light and dark, remembered. PrimeVue is configured with this same class as its
 * darkModeSelector (see main.ts), so toggling it on <html> flips the theme
 * tokens and the color-scheme the rest of the CSS builds on.
 *
 * index.html applies the class before first paint using the same key, so a dark
 * reader does not get a flash of white. Keep the two in step.
 */
export const DARK_CLASS = 'app-dark'
export const STORAGE_KEY = 'pike-portfolio-scheme'

export type Scheme = 'light' | 'dark'

/** What was chosen last, or what the system asks for. */
function initial(): Scheme {
  const stored = localStorage.getItem(STORAGE_KEY)

  if (stored === 'light' || stored === 'dark') {
    return stored
  }

  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

// Module level, so every component shares the one scheme.
const scheme = ref<Scheme>(initial())

watchEffect(() => {
  document.documentElement.classList.toggle(DARK_CLASS, scheme.value === 'dark')
  localStorage.setItem(STORAGE_KEY, scheme.value)
})

export function useTheme() {
  function toggle(): void {
    scheme.value = scheme.value === 'dark' ? 'light' : 'dark'
  }

  return { scheme, toggle }
}
