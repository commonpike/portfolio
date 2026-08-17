# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Intent

A portfolio of past projects, stored as plain files on disk, read by one central PHP library (`php/Portfolio.php`) and rendered by a handful of small exporter scripts.

**Stay slim.** No framework, no composer dependencies, no build step, no database, no class hierarchies beyond the two classes described here. If a change adds indirection or tooling, it's probably the wrong change.

## Layout

```
php/                    all the code — the library and the exporters
  Portfolio.php         the central library — the only file that touches disk
  markdown.php          exporter: Markdown, grouped by year
  json.php              exporter: JSON, callable from the CLI or over HTTP
tests/Portfolio.php     tester for the library
tests/markdown.php      tester for that exporter
tests/json.php          tester for that exporter, CLI only
tests/common.php        shared setup and helpers — not a tester
tests/fixtures/         an asset root of its own, used only by the testers
projects/               the fallback asset root
exports/                generated output
<BASEDIR>/
  <year>/
    <project>/          one folder per project, holding its assets
    _<project>/         parked — skipped by Portfolio::projects()
```

One tester per script, named after it: `tests/Portfolio.php` covers the library, `tests/markdown.php` covers only that exporter's formatting. A new exporter gets its own tester and doesn't re-test the library. `tests/` stays at the root, outside `php/`, and reaches the code through `ROOT . '/php/…'`.

Exporter scripts live in `php/`, next to `Portfolio.php`, and `require` it.

The asset root is the `BASEDIR` constant at the top of `php/Portfolio.php` — an external archive volume, so a run finds nothing when that volume isn't mounted. `Portfolio::dir()` returns it, falling back to `projects/` in the repository root — one level up from the library — when `BASEDIR` is empty. Read assets only through `Portfolio::dir()`; never hardcode the path in an exporter.

`BASEDIR` is defined with `defined() || define()`, so a script that defines it *before* requiring the library wins — that is how the testers point at fixtures instead of the archive. For the same reason, scripts `require_once` the library rather than `require` it.

The asset collection is still being edited, so don't treat any particular folder or `.txt` as guaranteed to exist, and don't rename assets to match the code. Properties are declared in `Project`; whatever the assets don't provide stays at its default.

## Architecture

`Portfolio` (static methods) does all discovery and parsing; `Project` is a plain data object. Exporters hold formatting only — no globbing, no `file_get_contents`, so every exporter sees identical data.

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
| `title` | string | `title.txt`, else `ucfirst($slug)` |
| `link` | string | `link.txt`, with `https://` prepended unless it already has a scheme |
| `roles` | array | `roles.txt`, split on `/`, `,` or newline, trimmed, empties dropped |
| `technologies` | array | `technologies.txt`, split the same way |
| `type`, `owner`, `description`, `design`, `programming`, `production`, `content` | string | the same-named `.txt` |
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
php php/markdown.php > portfolio.md         # every project
php php/markdown.php --rank=60 > short.md   # only projects ranked 60 or higher
```

Options are long-form and parsed with `getopt()`, listed in one `OPTIONS` const that also feeds the usage message. Each option that narrows the selection appends a predicate to a `$filters` array, and a project must pass all of them — so adding `--year` or `--type` is one `if` block, not a rewrite. An unparseable value exits 1 with usage on stderr.

`json.php` emits every property of every project and serves both callers:

```sh
php php/json.php --rank=60 --type=website --offset=10 --limit=5
GET php/json.php?rank=60&type=website&offset=10&limit=5
```

An exporter that answers to HTTP as well keeps the difference in three places and nowhere else: `parameters()` reads `getopt()` or `$_GET` (unknown query keys are dropped), `fail()` writes usage to stderr and exits 1 or replies `400` with a JSON error body, and the `Content-Type` header is sent only when not on the CLI. Everything after that is SAPI-agnostic. A value that arrives repeated or as an array (`--rank=1 --rank=2`, `?rank[]=1`) fails the same way a malformed one does, rather than being silently coerced.

`type` matches case-insensitively; `offset` and `limit` are applied with `array_slice()` *after* filtering, so paging walks the narrowed list.

Conventions worth keeping in new exporters:

- A line is printed only when the values it uses are non-empty, and a group whose lines all vanish takes its blank line with it — no stray gaps for sparse projects.
- Arrays render comma-separated.
- Group by year with a heading emitted on year change, relying on the library's sort rather than re-sorting.
- Build output from small helpers (`bullet()`, `quote()`, `blocks()` in `markdown.php`) instead of interleaving `echo` with conditionals.
- Prefix `images`/`files`/`preview` paths with whatever base URL or directory the format needs; the library stores them relative to the asset root.

## Testing

```sh
php tests/Portfolio.php && php tests/markdown.php && php tests/json.php
```

Each prints one line per check and exits 1 if any failed, so they double as a commit check. `tests/json.php` covers only that exporter's CLI: the HTTP path differs solely in `parameters()`, `fail()` and the `Content-Type` header, and testing it would need a web server, which can't be assumed available. Run them after any change to `php/Portfolio.php` or an exporter — they are the reason the edge cases don't need rebuilding by hand.

`tests/common.php` is shared setup, not a tester: it defines `BASEDIR` as `tests/fixtures/` *before* requiring the library, sets `ROOT` (`__DIR__ . '/..'`) so testers run from any working directory, and provides `section()`, `check()` and `conclude()`. A tester is then just `require_once __DIR__ . '/common.php';` followed by assertions, ending in `conclude()`.

Because that root holds nothing but fixtures, assertions cover the *whole* listing and the golden output is the *whole* document — no year filtering, no post-processing. Keep it that way: a fixture added under a new year changes both.

The fixtures sit in `tests/fixtures/2099/` and `tests/fixtures/_2098/`:

| fixture | covers |
|---|---|
| `2099/full` | every parsed property, both credit fields, images vs. other files, `preview.*`, unrecognised and reserved-name text assets |
| `2099/sparse` | defaults: `title` from slug, empty strings and arrays, no preview |
| `2099/bad-rank` | a `rank.txt` that isn't numeric |
| `2099/no-preview` | `preview` falling back to the first image |
| `2099/rank-low` | low rank for sort order, and a link that already has a scheme |
| `2099/_draft`, `2099/empty`, `2099/ignored-only` | the three reasons a folder is skipped — underscored, empty, only ignored files |
| `_2098/hidden-year` | an underscored year |

`2099/empty` and `2099/ignored-only` hold a `.gitkeep`, because git tracks no empty folders and both cases would otherwise vanish on clone. The library skips dot-prefixed entries, so the placeholder is invisible to it and the folders still read as having nothing in them.

To add a case, add a folder under `tests/fixtures/2099/` and an assertion in the relevant tester, then update the listing check in `tests/Portfolio.php` and the golden output in `tests/markdown.php` to match — both cover everything the fixture root contains.

The exporter checks shell out to `php -r 'define("BASEDIR", …); require "php/markdown.php";' -- --rank=51`, so `getopt()` sees a real command line and the option path is genuinely exercised.

## Note

The root `.env` holds an API key unrelated to this project. Don't read it, load it, or reference it from any script.
