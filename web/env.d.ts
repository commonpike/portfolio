/// <reference types="vite/client" />

/** The settings in .env, typed. Everything Vite exposes is a string. */
interface ImportMetaEnv {
  /** URL of the JSON exporter, php/json.php. */
  readonly VITE_PORTFOLIO_JSON_URL: string
  /** Base URL for the relative image and file paths that exporter returns. */
  readonly VITE_ASSET_BASE_URL: string
  /** PrimeVue license key. Kept in .env only, never in .env.dist. */
  readonly VITE_PRIMEVUE_LICENSE_KEY?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
