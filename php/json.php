<?php
/**
 * Exporter: the portfolio as JSON — every property of every project.
 *
 *   php php/json.php                               every project
 *   php php/json.php --rank=60 --type=website      narrowed
 *   php php/json.php --offset=10 --limit=5         a page of the list
 *   php php/json.php --basedir=/mnt/other          read another asset root
 *   php php/json.php --refresh                     rebuild the cache first
 *
 * The same parameters are read from the query string when this is requested
 * over HTTP, the CLI_ONLY ones excepted:
 *
 *   GET php/json.php?rank=60&type=website&offset=10&limit=5
 *
 * The library is always asked for the whole listing, which is cached in cache/
 * and narrowed here — so every combination of parameters is served from one read
 * of the asset root, which matters when that is an archive volume.
 *
 * Bad input exits 1: with usage on stderr from the command line, as a 400 with
 * a JSON error body over HTTP.
 */

/** Accepted parameters; a trailing ':' means the parameter takes a value. */
const OPTIONS = ['basedir:', 'rank:', 'type:', 'offset:', 'limit:', 'refresh'];

/**
 * Parameters the command line may set but a query string may not: choosing the
 * asset root would let a request have any readable directory listed back, and
 * forcing a rebuild would let it order a rescan of the archive at will.
 */
const CLI_ONLY = ['basedir', 'refresh'];

/** Where the cached listings live, and how many seconds one stays fresh. */
const CACHE_DIR = __DIR__ . '/cache';
const CACHE_TTL = 600;

/** How long a cold scan may take when this is answered over HTTP; see listing(). */
const SCAN_SECONDS = 300;

/**
 * Who may read this from a browser. The listing is public and read-only, and a
 * front end is served from another origin while it is being developed, so any
 * origin may. Only plain GETs are answered — nothing here needs a preflight.
 */
const ALLOW_ORIGIN = '*';

/** Called from the command line rather than over HTTP? */
function cli(): bool
{
    return PHP_SAPI === 'cli';
}

/** Parameters from the command line or the query string, whichever applies. */
function parameters(): array
{
    if (!cli()) {
        $names = array_diff(
            array_map(fn($option) => rtrim($option, ':'), OPTIONS),
            CLI_ONLY
        );

        return array_intersect_key($_GET, array_flip($names));
    }

    $options = getopt('', OPTIONS);

    return $options === false ? fail('could not read the options') : $options;
}

/** Report bad input the way the caller expects, and stop. */
function fail(string $message): never
{
    if (cli()) {
        $usage = implode('] [--', array_map(
            fn($option) => str_ends_with($option, ':') ? rtrim($option, ':') . '=<value>' : $option,
            OPTIONS
        ));
        fwrite(STDERR, "$message\nusage: php php/json.php [--$usage]\n");
        exit(1);
    }

    http_response_code(400);
    headers();
    echo json_encode(['error' => $message]), "\n";
    exit(1);
}

/** The headers every HTTP reply needs: what it is, and who may read it. */
function headers(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: ' . ALLOW_ORIGIN);
}

/** A whole number, or nothing: a repeated or malformed parameter is refused. */
function number($value, string $name): int
{
    if (!is_string($value) || !preg_match('/^\d+$/', $value)) {
        fail("$name must be a whole number");
    }

    return (int) $value;
}

/** The cache file for the asset root in use; another root gets another file. */
function cache(): string
{
    return CACHE_DIR . '/' . md5(Portfolio::dir()) . '.json';
}

/**
 * The whole listing as plain arrays, from the cache when it is there and fresh,
 * from the asset root otherwise — in which case the cache is rewritten.
 */
function listing(bool $refresh): array
{
    $file = cache();

    if (!$refresh && is_file($file) && filemtime($file) > time() - CACHE_TTL) {
        $cached = json_decode((string) file_get_contents($file), true);
        if (is_array($cached) && array_is_list($cached)) {
            return $cached;
        }
    }

    // Nothing was cached, so the whole asset root is about to be read — which
    // outlives a web server's max_execution_time when that root is a slow mount.
    // Only over HTTP: the command line runs with no limit to lift, and capping it
    // there would only cut an export short.
    if (!cli()) {
        set_time_limit(SCAN_SECONDS);
    }

    // Plain arrays rather than Project objects, so the cache holds exactly what
    // is served and the filters below read one shape either way.
    $projects = array_map(
        fn(Project $project) => get_object_vars($project),
        Portfolio::projects()
    );

    store($file, $projects);

    return $projects;
}

/** Write the cache. A cache that cannot be written is skipped, not fatal. */
function store(string $file, array $projects): void
{
    if (!is_dir(CACHE_DIR) && !@mkdir(CACHE_DIR, 0777, true)) {
        return;
    }

    // Written aside and renamed, so a concurrent reader never sees half a file.
    $temporary = $file . '.' . getmypid();

    if (@file_put_contents($temporary, json_encode($projects)) === false) {
        return;
    }

    @rename($temporary, $file);
}

$parameters = parameters();

// --basedir has to be settled before the library is required, because that is
// where BASEDIR gets its default. A wrapper that defined it already wins, as it
// does everywhere else. Only reachable from the command line: parameters() keeps
// CLI_ONLY out of the query string.
if (isset($parameters['basedir'])) {
    if (!is_string($parameters['basedir']) || !is_dir($parameters['basedir'])) {
        fail('basedir must be a directory');
    }
    defined('BASEDIR') || define('BASEDIR', realpath($parameters['basedir']));
}

require_once __DIR__ . '/Portfolio.php';

// One entry per parameter that narrows the selection; a project must pass all.
// They run here rather than in Portfolio::projects(), which is always asked for
// everything so that one cached listing can answer any set of parameters.
$filters = [];

if (isset($parameters['rank'])) {
    $rank = number($parameters['rank'], 'rank');
    $filters[] = fn(array $project) => $project['rank'] >= $rank;
}

if (isset($parameters['type'])) {
    if (!is_string($parameters['type'])) {
        fail('type must be a single value');
    }
    $type = strtolower(trim($parameters['type']));

    // An empty type narrows nothing: ?type= means any type.
    if ($type !== '') {
        $filters[] = fn(array $project) => strtolower($project['type']) === $type;
    }
}

// Paging is applied after filtering, so it walks the narrowed list.
$offset = isset($parameters['offset']) ? number($parameters['offset'], 'offset') : 0;
$limit = isset($parameters['limit']) ? number($parameters['limit'], 'limit') : null;

$projects = array_values(array_filter(
    listing(isset($parameters['refresh'])),
    function (array $project) use ($filters) {
        foreach ($filters as $accepts) {
            if (!$accepts($project)) {
                return false;
            }
        }

        return true;
    }
));

if (!cli()) {
    headers();
}

echo json_encode(
    array_slice($projects, $offset, $limit),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
), "\n";
