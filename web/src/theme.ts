import { definePreset } from '@primeuix/themes'
import Aura from '@primeuix/themes/aura'

/**
 * Aura in blue. The preset ships emerald as its primary colour; each shade below
 * points at Aura's own blue palette instead. Everything derived from primary —
 * primary.color, the focus ring, highlights, link colour — references these
 * shades, so none of it has to be restated here.
 */
export const AuraBlue = definePreset(Aura, {
  semantic: {
    primary: {
      50: '{blue.50}',
      100: '{blue.100}',
      200: '{blue.200}',
      300: '{blue.300}',
      400: '{blue.400}',
      500: '{blue.500}',
      600: '{blue.600}',
      700: '{blue.700}',
      800: '{blue.800}',
      900: '{blue.900}',
      950: '{blue.950}',
    },
  },
})
