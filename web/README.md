# pike::portfolio — web

The portfolio as a single-page app: Vue 3, TypeScript, Vite, PrimeVue. It holds no
data of its own — it fetches the whole listing from the JSON exporter in `../php`
and does its filtering, sorting and paging in the browser.

## Configuration

Both settings live in `.env`, which is **not** in git. `.env.dist` is the tracked
template, so start by copying it:

```sh
cp .env.dist .env
```

| setting | what it is |
|---|---|
| `VITE_PORTFOLIO_JSON_URL` | The URL of the JSON exporter, `php/json.php`. The app fetches it once, unfiltered, and narrows the result client-side. |
| `VITE_ASSET_BASE_URL` | The base URL for media. The exporter returns `images`, `files` and `preview` as paths relative to the asset root — `2012/wereldkiezer/preview.jpg` — and that root is an archive volume, not a web root, so this says where those files are actually served from. `assetUrl()` in `src/config.ts` joins the two. |
| `VITE_PRIMEVUE_LICENSE_KEY` | PrimeVue's license key. Empty in `.env.dist` on purpose — fill it in `.env` only. |

PrimeVue 5 is licensed software: free for individuals and small organisations
under its [Community License](https://primeui.dev/licenses), paid above that, and
either way it wants a key. Without one the library still works but logs
`PrimeUI license is not configured`. Note that a `VITE_` setting is compiled into
the bundle, so the key is readable in the built JavaScript — that is how a
browser-side license works, and it is why the key is kept out of git but cannot be
kept out of the page.

Both are read only in `src/config.ts`, and both are baked into the bundle by
`vite build` — they are build-time settings, not runtime ones, so changing either
means rebuilding. `.env.local` overrides `.env` if you want a machine-local
variant; it is git-ignored too.

A setting left empty is reported in the page rather than guessed at, so a fresh
clone that forgot the copy above says so instead of fetching `undefined`.

### Serving the two of them

The defaults in `.env.dist` assume PHP is serving the repository root:

```sh
php -S localhost:8000          # from the repository root, not from web/
npm run dev                    # from here
```

Two things to know about that pairing:

- **CORS.** `npm run dev` serves this site on its own port, so an absolute
  `VITE_PORTFOLIO_JSON_URL` is a cross-origin request. `php/json.php` sends
  `Access-Control-Allow-Origin: *` (its `ALLOW_ORIGIN` const) so the browser
  allows it, on the error replies as well as the successful ones. Only plain GETs
  are answered — there is no preflight handling, and none is needed for a request
  that sends no unsafelisted headers.
- **Media.** `VITE_ASSET_BASE_URL` only resolves to real files where those files
  are reachable over HTTP. The live assets sit on an archive volume outside the
  repository, so in development the local `projects/` fallback root is the thing
  to point at — or a copy, or a symlink.

## Project Setup

```sh
npm install
```

### Compile and Hot-Reload for Development

```sh
npm run dev
```

### Type-Check, Compile and Minify for Production

```sh
npm run build
```

### Lint with [ESLint](https://eslint.org/) and [oxlint](https://oxc.rs/)

```sh
npm run lint
npm run format
```

There are no tests here, by design: the testers in `../tests` cover the PHP that
produces the data.

## Layout

```
src/
  config.ts               the .env settings, and assetUrl() for media paths
  types.ts                Project, mirroring the PHP class of the same name
  router.ts               the three pages, and their titles
  theme.ts                Aura, in blue
  composables/
    useProjects.ts        the listing: one shared fetch, loaded on demand
    useTheme.ts           light/dark, remembered in localStorage
  views/
    PortfolioView.vue     pike::portfolio — intro and the listing
    CvView.vue            pike::cv
    ThisView.vue          about::this
  components/
    SiteHeader.vue        the nav, with the theme switch
    SiteIntro.vue         a page's heading, with its copy slotted in
    PageTitle.vue         a title with its :: in the primary colour
    ProjectsListing.vue   filters, paging, and the projects by year
    ProjectCard.vue       one project, in either view
    ListingSelect.vue     one labelled filter
    ListingPager.vue      00, 01, 02 …
  assets/main.css         base styles, on PrimeVue's design tokens
```

A page is one entry in `src/router.ts`: the title there is the whole of its
identity — nav text, heading and `document.title` all read it, which is why
`about::this` can differ from `pike::portfolio` in more than its last word.

Routing uses history mode, so a server that hosts the built site has to fall back
to `index.html` for unknown paths, or a refresh of `/cv` will 404. `npm run dev`
does that on its own.

The listing fetches everything once and narrows it in the browser: Type comes from
the types the projects actually declare, Show is a threshold on `rank`, Limit and
the pager slice what is left. Picking a type sets Show to `all`, since the two
filters multiply and a type with nothing highly ranked would come back empty. Grid and detail are the same markup under different
CSS, so switching neither refetches nor collapses an unfolded description.

## Theme

PrimeVue's [Aura](https://primevue.org/theming/) preset supplies the colours as
`--p-*` custom properties, so plain CSS in this project gets light and dark for
free — there are no dark-mode variants to write. Dark mode is a class on `<html>`
(`app-dark`) rather than a media query, so the switch in the header decides;
`index.html` applies it before first paint to avoid a flash of white.

Type is [Inter](https://rsms.me/inter/) for text and
[Space Grotesk](https://fonts.google.com/specimen/Space+Grotesk) for headings,
both self-hosted through Fontsource — no third-party font requests.

## Recommended IDE Setup

[VS Code](https://code.visualstudio.com/) + [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Recommended Browser Setup

- Chromium-based browsers (Chrome, Edge, Brave, etc.):
  - [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
  - [Turn on Custom Object Formatter in Chrome DevTools](http://bit.ly/object-formatters)
- Firefox:
  - [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)
  - [Turn on Custom Object Formatter in Firefox DevTools](https://fxdx.dev/firefox-devtools-custom-object-formatters/)

## Type Support for `.vue` Imports in TS

TypeScript cannot handle type information for `.vue` imports by default, so we replace the `tsc` CLI with `vue-tsc` for type checking. In editors, we need [Volar](https://marketplace.visualstudio.com/items?itemName=Vue.volar) to make the TypeScript language service aware of `.vue` types.

## Customize configuration

See [Vite Configuration Reference](https://vite.dev/config/).
