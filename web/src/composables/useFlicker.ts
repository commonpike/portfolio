import { onScopeDispose, ref, type Ref } from 'vue'

/**
 * The colons flicker: every 7.5 seconds or so, one or more of the `::` on the page
 * flashes for a moment. Decoration, and meant to be half-missed.
 *
 * One timer for the whole page rather than one per title, because a round is the
 * event — four titles each on their own clock would flicker four times as often as
 * asked. Every mounted title lends this a boolean and the timer decides which of
 * them go up together.
 */

/** How long a colon stays lit — long enough to catch, short enough to doubt. */
const FLASH = 90

/** The wait between rounds: 7.5 seconds, give or take half of it. */
const MIN = 5000
const MAX = 10000

/** How likely each colon is to join a round. One always does — see `pick()`. */
const SHARE = 0.35

/** Every mounted title's lamp. The timer runs while this is not empty. */
const lamps = new Set<Ref<boolean>>()

let timer: ReturnType<typeof setTimeout> | undefined

/**
 * Which lamps this round lights. Each one takes its own chance, so the number
 * varies — but never to none: a round nobody saw is a round wasted.
 */
function pick(): Ref<boolean>[] {
  const all = [...lamps]
  const chosen = all.filter(() => Math.random() < SHARE)

  return chosen.length > 0 ? chosen : [all[Math.floor(Math.random() * all.length)]!]
}

function round(): void {
  const chosen = pick()

  chosen.forEach((lamp) => (lamp.value = true))
  setTimeout(() => chosen.forEach((lamp) => (lamp.value = false)), FLASH)

  schedule()
}

function schedule(): void {
  timer = setTimeout(round, MIN + Math.random() * (MAX - MIN))
}

/**
 * Whether this title's colons are lit right now. The first caller starts the
 * timer and the last one to go stops it, so nothing ticks on a page without a
 * title on it.
 */
export function useFlicker(): Ref<boolean> {
  const lit = ref(false)

  // Nothing at all where less motion was asked for: an unrequested blink is
  // precisely what that setting is about, and the site loses only a wink.
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return lit
  }

  lamps.add(lit)

  if (timer === undefined) {
    schedule()
  }

  onScopeDispose(() => {
    lamps.delete(lit)

    if (lamps.size === 0) {
      clearTimeout(timer)
      timer = undefined
    }
  })

  return lit
}
