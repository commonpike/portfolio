# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Intent

A portfolio of past projects, stored as plain files on disk, read by one central PHP library (`php/Portfolio.php`) and rendered by a handful of small exporter scripts. One script goes the other way: `php/import.php` writes asset folders from a CSV.

**Stay slim.** No framework, no composer dependencies, no build step, no database, no class hierarchies beyond the two classes described here. If a change adds indirection or tooling, it's probably the wrong change.

That rule is about the PHP. `web/` holds a TypeScript/Vue single-page app under deliberately different constraints — npm dependencies and a build step are fine there, and it has no testers, since the testers here cover the code that produces its data. It reads the portfolio only through `php/json.php`. See `web/README.md`.

## Layout

```
php/                    all the code — the library, the exporters, the importer
  Portfolio.php         the central library — the only file that reads the assets
  markdown.php          exporter: Markdown, grouped by year
  json.php              exporter: JSON, callable from the CLI or over HTTP
  import.php            importer: a CSV of projects into asset folders
  cache/                json.php's cached listings, one per asset root
tests/Portfolio.php     tester for the library
tests/markdown.php      tester for that exporter
tests/json.php          tester for that exporter, CLI only
tests/import.php        tester for the importer
tests/common.php        shared setup and helpers — not a tester
tests/fixtures/         an asset root of its own, used only by the testers
projects/               the fallback asset root
exports/                generated output
web/                    the Vue SPA — its own conventions, see web/README.md
<BASEDIR>/
  <year>/
    <project>/          one folder per project, holding its assets
    _<project>/         parked — skipped by Portfolio::projects()
```

One tester per script, named after it: `tests/Portfolio.php` covers the library, `tests/markdown.php` covers only that exporter's formatting. A new exporter gets its own tester and doesn't re-test the library. `tests/` stays at the root, outside `php/`, and reaches the code through `ROOT . '/php/…'`.

Exporter and importer scripts live in `php/`, next to `Portfolio.php`, and `require` it.

The asset root is the `BASEDIR` constant at the top of `php/Portfolio.php` — an external archive volume, so a run finds nothing when that volume isn't mounted. `Portfolio::dir()` returns it, falling back to `projects/` in the repository root — one level up from the library — when `BASEDIR` is empty. Read assets only through `Portfolio::dir()`; never hardcode the path in an exporter.

`BASEDIR` is defined with `defined() || define()`, so a script that defines it *before* requiring the library wins — that is how `--basedir` works in the exporters, and how `tests/common.php` points at fixtures instead of the archive. For the same reason, scripts `require_once` the library rather than `require` it.

The asset collection is still being edited, so don't treat any particular folder or `.txt` as guaranteed to exist, and don't rename assets to match the code. Properties are declared in `Project`; whatever the assets don't provide stays at its default.

## Architecture

`Portfolio` (static methods) does all discovery and parsing; `Project` is a plain data object. Exporters hold formatting only — no globbing, no reading of assets, so every exporter sees identical data. The one thing an exporter may touch on disk is its own cache under `php/cache/`, as `json.php` does; that is a copy of what the library already returned, never a second way of reading the assets.

`import.php` is the only script that writes assets, and it writes nothing else: it never reads them back, so the library stays the one way in. It gets the root the same way everything else does, from `Portfolio::dir()`.

```php
require 'Portfolio.php';

Portfolio::projects();                                    // all, sorted
Portfolio::projects(fn(Project $p) => $p->year >= 2000);  // filtered
Portfolio::project('2012', 'wereldkiezer');               // one
Portfolio::dir();                                         // absolute asset root
```

`Portfolio::projects()` takes an optional callback and returns only the projects it accepts — filtering lives in the library so exporters don't each reimplement it. Any predicate over a `Project` works: by year, `type`, `rank`, a non-empty `link`, a technology in `technologies`. The result is re-indexed from 0.

Sort order is year descending, then `rank` descending (higher rank first), then slug ascending.

`Portfolio::projects()` skips, before the callback ever runs:

- **year or project folders starting with `_`** — `_2005/` or `2003/_draft/` are work in progress, parked in place. Hidden (dot-prefixed) entries go too, which is also what keeps `.DS_Store` out.
- **projects that yielded nothing** — an empty folder, or one holding only files the library ignores. Detected in `projects()` by comparing the project against a freshly constructed one: if every property is still at its default, there was nothing to read.

`Portfolio::project()` applies neither rule — asking for a specific project by year and slug always reads it, which is how you preview an underscored draft.

Keep the library agnostic about output: no echoing, no HTML, no CLI argument handling inside it.

## How assets map to properties

One `.txt` per property, named after the property. Everything is trimmed on read. Missing assets leave the declared default, so exporters never need `isset`.

| property | type | source |
|---|---|---|
| `year` | string | parent folder |
| `slug` | string | project folder |
| `path` | string | `"<year>/<slug>"`, relative to the asset root |
| `rank` | int | `rank.txt`; `50` when missing or not numeric |
| `type` | string | `type.txt`; `'project'` when missing or empty |
| `title` | string | `title.txt`, else `ucfirst($slug)` |
| `link` | string | `link.txt`, with `https://` prepended unless it already has a scheme |
| `roles` | array | `roles.txt`, split on `/`, `,` or newline, trimmed, empties dropped |
| `technologies` | array | `technologies.txt`, split the same way |
| `owner`, `description`, `design`, `programming`, `production`, `content` | string | the same-named `.txt` |
| `images` | array | every image file, as paths relative to the asset root |
| `files` | array | every other non-text file, same path form |
| `preview` | string | the `preview.*` image, else `images[0]`, else `''` |
| `other` | array | any *unrecognised* `.txt`, as `name => contents` |

Image filenames are not significant beyond `preview.*` — any other image just lands in `images`, so projects never end up with differing property names.

Non-alphanumerics in a filename collapse to underscores (`my notes.txt` → `my_notes`), keeping properties reachable with plain `->` syntax.

### Adding a property

Declare it on `Project` with a default and add a `.txt` of that name to the asset folders. Until it's declared, its asset still reads — into `other` — so nothing breaks in the meantime. Parsed properties (`rank`, `link`, `roles`, `technologies`) get a case in `Project::setText()`; values derived from other properties go in `Project::complete()`, which runs once after a folder is read.

`Project::RESERVED` lists the properties built from the folder rather than a `.txt`. A stray `preview.txt` or `year.txt` is diverted into `other` instead of overwriting them — keep that list in sync when adding derived properties.

## Exporters

One script per output format, each a thin pass over `Portfolio::projects()`. `markdown.php` is the reference:

```sh
php php/markdown.php > portfolio.md       # every project
php php/markdown.php --rank=60 > short.md # only projects ranked 60 or higher
php php/markdown.php --basedir=/mnt/other # read another asset root
```

Options are long-form and parsed with `getopt()`, listed in one `OPTIONS` const that also feeds the usage message. Each option that narrows the selection appends a predicate to a `$filters` array, and a project must pass all of them — so adding `--year` or `--type` is one `if` block, not a rewrite. An unparseable value exits 1 with usage on stderr.

Every exporter takes `--basedir`, which overrides the asset root for that one run. It is the one option handled *before* `require`ing the library, because that is where `BASEDIR` gets its default — so the option block sits above the `require`, not with the filters, and defines the constant with `defined('BASEDIR') || define(...)` so a wrapper that already set it keeps winning. A path that isn't a directory is refused like any other bad value; the value is passed through `realpath()`, keeping `Portfolio::dir()` absolute.

`json.php` emits every property of every project and serves both callers:

```sh
php php/json.php --basedir=/mnt/other --rank=60 --type=website --offset=10 --limit=5
php php/json.php --refresh
GET php/json.php?rank=60&type=website&offset=10&limit=5
```

It asks the library for the *whole* listing every time and narrows it itself, so its filters are predicates over plain arrays (`$project['rank']`) rather than over `Project` objects. That is what makes the cache worth having: one cached document answers any combination of parameters, where a cached *filtered* result could only answer the run that produced it.

`listing()` returns that document, from `php/cache/<md5 of the asset root>.json` when the file is there and younger than `CACHE_TTL`, otherwise from `Portfolio::projects()` — writing the cache on the way out, to a temporary name it renames, so a concurrent reader never sees half a file. The name keys on `Portfolio::dir()`, so a `--basedir` run neither reads nor poisons another root's cache. A cache that can't be written is skipped rather than fatal: it is only a cache, and the web server may not own the folder. `--refresh` forces a rebuild and is `CLI_ONLY`, so a request cannot order a rescan of the archive.

The cached arrays are what `json_encode()` would have produced from the `Project` objects, so the served document is byte-identical either way — which is why the cache holds associative arrays and is decoded with `json_decode(…, true)`: as `stdClass` an empty `other` would come back `{}` instead of `[]`.

`--basedir` is command-line only. `CLI_ONLY` lists the parameters a query string may not set, and `parameters()` drops them from `$_GET` — letting a request choose the asset root would have the exporter list back any readable directory. A new parameter is query-string-readable unless it goes in that list.

An exporter that answers to HTTP as well keeps the difference in three places and nowhere else: `parameters()` reads `getopt()` or `$_GET` (unknown and `CLI_ONLY` query keys are dropped), `fail()` writes usage to stderr and exits 1 or replies `400` with a JSON error body, and `headers()` — `Content-Type` plus `Access-Control-Allow-Origin: ALLOW_ORIGIN`, so a front end on another origin may read it — is called only when not on the CLI. The error replies send those headers too, or a browser would hide the error body. Everything after that is SAPI-agnostic. A value that arrives repeated or as an array (`--rank=1 --rank=2`, `?rank[]=1`) fails the same way a malformed one does, rather than being silently coerced.

`type` matches case-insensitively; `offset` and `limit` are applied with `array_slice()` *after* filtering, so paging walks the narrowed list.

Conventions worth keeping in new exporters:

- A line is printed only when the values it uses are non-empty, and a group whose lines all vanish takes its blank line with it — no stray gaps for sparse projects.
- Arrays render comma-separated.
- Group by year with a heading emitted on year change, relying on the library's sort rather than re-sorting.
- Build output from small helpers (`bullet()`, `quote()`, `blocks()` in `markdown.php`) instead of interleaving `echo` with conditionals.
- Prefix `images`/`files`/`preview` paths with whatever base URL or directory the format needs; the library stores them relative to the asset root.

## The importer

`import.php` fills an asset root from a CSV — the collection is maintained as a spreadsheet, and this is how a revision of it reaches disk:

```sh
php php/import.php --basedir=projects            # into the local asset root
php php/import.php --basedir=projects --dry-run  # report without writing
php php/import.php --basedir=projects --force    # overwrite existing folders
php php/import.php --csv=exports/other.csv       # read another listing
```

It shares the exporters' option conventions — one `OPTIONS` const feeding the usage message, `--basedir` settled above the `require`, a bad value exiting 1 with usage on stderr — and defaults `--csv` to `exports/portfolio.csv`.

One row becomes `<year>/<slug>/`, one column becomes one `<property>.txt` in it. The mapping is the *header*, not a list in the code: only `yyyy` and `slug` are held back, because they name the folders, so a column added to the spreadsheet needs no change here and lands in `Project::$other` until the property is declared. Column names are normalised the way `Portfolio::name()` reads them back, so `annual report` and `annual_report.txt` are the same property. Values are trimmed and written without a trailing newline, matching the assets already on disk; an empty column writes no file at all, leaving the declared default rather than an empty asset.

A row is refused, reported on stderr and skipped — with the run exiting 1 — when its year isn't four digits, its slug wouldn't survive the round trip through a folder name, or its folder already exists without `--force`. Refusing rather than coercing is the point: a slug the library would read back differently is a silent rename.

`--force` makes a folder *match* its row: the columns' own `.txt` files are rewritten, and one whose column is now empty is removed, so emptying a cell empties the property. Nothing else in the folder is touched — images, and text assets the CSV has no column for, stay. That is what makes a re-import after a spreadsheet edit safe.

Two things worth knowing when a row seems to vanish after an import. A folder whose only asset is a `title.txt` holding exactly the ucfirst'ed slug is indistinguishable from an empty one, so `Portfolio::projects()` skips it — a row needs one value the defaults don't already supply. And an underscored slug is written as it stands, which parks it in place; that is deliberate, not an import failure.

## Testing

```sh
php tests/Portfolio.php && php tests/markdown.php && php tests/json.php && php tests/import.php
```

Each prints one line per check and exits 1 if any failed, so they double as a commit check. `tests/json.php` covers only that exporter's CLI: the HTTP path differs solely in `parameters()`, `fail()` and the `Content-Type` header, and testing it would need a web server, which can't be assumed available. Run them after any change to `php/Portfolio.php` or an exporter — they are the reason the edge cases don't need rebuilding by hand.

`tests/import.php` is the one tester that writes: it builds both its CSV and its target root under `sys_get_temp_dir()`, so it stays off `tests/fixtures/` — an asset root the other testers assert over *in full*, which an import would break — and clears both afterwards. Its one end-to-end check reads the result back through `php/json.php` with `--refresh`, since that exporter caches per asset root and the temporary root keeps its name between runs; the cache file is unlinked at the end, the same way `tests/json.php` leaves none behind.

`tests/common.php` is shared setup, not a tester: it defines `BASEDIR` as `tests/fixtures/` *before* requiring the library, sets `ROOT` (`__DIR__ . '/..'`) so testers run from any working directory, and provides `section()`, `check()` and `conclude()`. A tester is then just `require_once __DIR__ . '/common.php';` followed by assertions, ending in `conclude()`.

Because that root holds nothing but fixtures, assertions cover the *whole* listing and the golden output is the *whole* document — no year filtering, no post-processing. Keep it that way: a fixture added under a new year changes both.

The fixtures sit in `tests/fixtures/2099/` and `tests/fixtures/_2098/`:

| fixture | covers |
|---|---|
| `2099/full` | every parsed property, both credit fields, images vs. other files, `preview.*`, unrecognised and reserved-name text assets |
| `2099/sparse` | defaults: `title` from slug, empty strings and arrays, no preview |
| `2099/bad-rank` | a `rank.txt` that isn't numeric, and an empty `type.txt` |
| `2099/no-preview` | `preview` falling back to the first image |
| `2099/rank-low` | low rank for sort order, and a link that already has a scheme |
| `2099/_draft`, `2099/empty`, `2099/ignored-only` | the three reasons a folder is skipped — underscored, empty, only ignored files |
| `_2098/hidden-year` | an underscored year |

`2099/empty` and `2099/ignored-only` hold a `.gitkeep`, because git tracks no empty folders and both cases would otherwise vanish on clone. The library skips dot-prefixed entries, so the placeholder is invisible to it and the folders still read as having nothing in them.

To add a case, add a folder under `tests/fixtures/2099/` and an assertion in the relevant tester, then update the listing check in `tests/Portfolio.php` and the golden output in `tests/markdown.php` to match — both cover everything the fixture root contains.

The exporter checks shell out to `php php/markdown.php --basedir=<fixtures> --rank=51`, so `getopt()` sees a real command line and the option path is genuinely exercised. Pointing a subprocess at the fixtures is what `--basedir` is for, which also means every one of those checks depends on it; the helper takes the root as a parameter, so passing a bad one covers the refusal.

`tests/json.php` cannot cover `CLI_ONLY` — that `basedir` and `refresh` are ignored in a query string is only observable over HTTP. It was verified by hand against `php -S`, and stays outside the testers along with the rest of the HTTP path.

The cache checks work by tampering: a cache holding a project the fixtures cannot produce proves a listing was read from it rather than from the asset root, and `touch()`ing the file into the past covers expiry without waiting for it. The tester `unlink`s the fixture cache before the first check and after the last, so a leftover cache never decides a run and none is left behind.

## Note

The root `.env` holds an API key unrelated to this project. Don't read it, load it, or reference it from any script.
