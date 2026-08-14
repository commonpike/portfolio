# Portfolio

A portfolio of past projects kept as plain files on disk — one folder per project, one text file per field — read by a single PHP library and turned into whatever format is needed: Markdown, a CV, a web page.

No framework, no dependencies, no build step, no database. PHP 8.1 or newer.

## Quick start

```sh
php markdown.php > exports/portfolio.md    # every project
php markdown.php --rank=60                 # only projects ranked 60 or higher

php json.php --type=website --limit=10     # every property, as JSON
```

`json.php` also answers over HTTP, reading the same parameters from the query string: `json.php?rank=60&type=website&offset=10&limit=5`.

## Where the assets live

The asset root is the `BASEDIR` constant at the top of `Portfolio.php`, currently an external archive volume — so nothing is found when that volume isn't mounted. With `BASEDIR` empty it falls back to `./projects`.

Assets are deliberately not in this repository; only the code is.

## Adding a project

Create `<year>/<slug>/` under the asset root and drop files in it:

```
2012/wereldkiezer/
  description.txt
  technologies.txt
  preview.jpg
```

- **Year folder** — four digits, becomes `year`.
- **Project folder** — its name becomes `slug`, and the fallback `title`.
- **`<name>.txt`** — the file's *contents* become the `<name>` property: `description.txt` fills `description`, `roles.txt` fills `roles`. A text file with no matching property is still read, into `other`.
- **Images** — collected into `images` as paths relative to the asset root. One named `preview.*` also becomes `preview`; without it, `preview` is the first image.
- **Anything else** — PDFs, archives, aliases — collected into `files`.

Four text files are parsed rather than taken literally: `rank.txt` is a number (default 50, higher sorts first), `link.txt` gains `https://` when it has no scheme, and `roles.txt` and `technologies.txt` are split on slashes, commas and newlines.

Nothing needs registering — the library reads whatever is there. The `Project` class in `Portfolio.php` is the definitive list of recognised filenames.

Prefix a year or project folder with `_` to park it — `_2026/`, `2012/_draft/` — and it stays out of every listing while remaining readable if asked for by name. Folders with nothing readable in them are skipped too.

## Code

| file | |
|---|---|
| `Portfolio.php` | the library: reads the assets, returns `Project` objects. The only file that touches disk |
| `markdown.php` | exporter: Markdown, grouped by year |
| `json.php` | exporter: JSON, from the command line or over HTTP |
| `tests/` | one tester per script, plus the fixtures they run against |
| `projects/` | local asset root; contents ignored by git |
| `exports/` | generated output; ignored by git |

`Portfolio::projects()` returns every project, newest year first, then by rank; pass a callback to filter:

```php
require 'Portfolio.php';

foreach (Portfolio::projects(fn(Project $p) => $p->year >= 2010) as $project) {
    echo $project->title, ': ', implode(', ', $project->technologies), "\n";
}
```

A new export format is a new script next to `markdown.php` that loops over that list and prints. Formatting only — discovery and parsing belong in the library, so every exporter sees identical data.

## Tests

```sh
php tests/Portfolio.php && php tests/markdown.php && php tests/json.php
```

Each prints one line per check and exits non-zero if any failed. They run against the fixtures in `tests/fixtures/`, never the real archive.
