/**
 * Everything this site needs to know about where its data lives, read from .env
 * at build time. Nothing else should touch import.meta.env.
 *
 * .env is not in git — a fresh clone has to copy .env.dist over — so a setting
 * being absent is a normal thing to happen, and missingSettings() exists to say
 * which one rather than let the site fetch "undefined".
 */

export const config = {
  /** The JSON exporter to fetch projects from. */
  jsonUrl: import.meta.env.VITE_PORTFOLIO_JSON_URL ?? '',
  /** Base URL for the relative paths that exporter returns. */
  assetBaseUrl: import.meta.env.VITE_ASSET_BASE_URL ?? '',
  /**
   * PrimeVue's license key. Not required for the site to work — without it the
   * library logs a notice — so it is deliberately not in missingSettings().
   */
  primevueLicense: import.meta.env.VITE_PRIMEVUE_LICENSE_KEY ?? '',
} as const

/** The names of the settings that .env did not provide. */
export function missingSettings(): string[] {
  const required = {
    VITE_PORTFOLIO_JSON_URL: config.jsonUrl,
    VITE_ASSET_BASE_URL: config.assetBaseUrl,
  }

  return Object.entries(required)
    .filter(([, value]) => value === '')
    .map(([name]) => name)
}

/**
 * A usable URL for one of the paths in a project's images, files or preview.
 * They arrive relative to the asset root ("2012/wereldkiezer/preview.jpg"),
 * which is not the web root, so they only resolve against the asset base URL.
 */
export function assetUrl(path: string): string {
  if (path === '') {
    return ''
  }

  return `${config.assetBaseUrl.replace(/\/+$/, '')}/${path.replace(/^\/+/, '')}`
}
