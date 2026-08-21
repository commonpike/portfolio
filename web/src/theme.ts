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

/**
 * Aura in #f60040. Same idea as AuraBlue, but the shades are literal: that red
 * is not one of Aura's palettes, so the whole ramp is spelled out. It was built
 * by holding the hue and saturation of #f60040 (hsl 344.4 100%) and walking the
 * lightness, so 500 is exactly the colour asked for and every other shade reads
 * as the same red.
 *
 * `color` is restated because Aura's own default is
 * `light-dark({primary.500}, {primary.400})` — it lightens the primary in dark
 * mode for contrast against a near-black surface. Here that would mean the site
 * showed #f60040 in light mode and #ff4273 in dark, and `--p-primary-color` is
 * the brand colour rather than a component accent: it draws the `::` in the page
 * title, the header rule, the focus outlines and every link. So it points at 500
 * in both modes, and `hoverColor`/`activeColor` are shifted one shade to match,
 * keeping Aura's habit of stepping darker in light mode and lighter in dark.
 */
export const AuraRed = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#fff0f4',
      100: '#ffe0e8',
      200: '#ffbdce',
      300: '#ff8aa8',
      400: '#ff4273',
      500: '#f60040',
      600: '#d10036',
      700: '#a8002c',
      800: '#810324',
      900: '#66051e',
      950: '#3c0211',
      color: '{primary.500}',
      hoverColor: 'light-dark({primary.600}, {primary.400})',
      activeColor: 'light-dark({primary.700}, {primary.300})',
    },
  },
})
