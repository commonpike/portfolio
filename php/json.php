<?php
/**
 * Exporter: the portfolio as JSON — every property of every project.
 *
 *   php php/json.php                               every project
 *   php php/json.php --rank=60 --type=website      narrowed
 *   php php/json.php --offset=10 --limit=5         a page of the list
 *
 * The same parameters are read from the query string when this is requested
 * over HTTP:
 *
 *   GET php/json.php?rank=60&type=website&offset=10&limit=5
 *
 * Bad input exits 1: with usage on stderr from the command line, as a 400 with
 * a JSON error body over HTTP.
 */

require_once __DIR__ . '/Portfolio.php';

/** Accepted parameters; a trailing ':' means the parameter takes a value. */
const OPTIONS = ['rank:', 'type:', 'offset:', 'limit:'];

/** Called from the command line rather than over HTTP? */
function cli(): bool
{
    return PHP_SAPI === 'cli';
}

/** Parameters from the command line or the query string, whichever applies. */
function parameters(): array
{
    if (!cli()) {
        $names = array_map(fn($option) => rtrim($option, ':'), OPTIONS);

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
            fn($option) => rtrim($option, ':') . '=<value>',
            OPTIONS
        ));
        fwrite(STDERR, "$message\nusage: php php/json.php [--$usage]\n");
        exit(1);
    }

    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message]), "\n";
    exit(1);
}

/** A whole number, or nothing: a repeated or malformed parameter is refused. */
function number($value, string $name): int
{
    if (!is_string($value) || !preg_match('/^\d+$/', $value)) {
        fail("$name must be a whole number");
    }

    return (int) $value;
}

$parameters = parameters();

// One entry per parameter that narrows the selection; a project must pass all.
$filters = [];

if (isset($parameters['rank'])) {
    $rank = number($parameters['rank'], 'rank');
    $filters[] = fn(Project $project) => $project->rank >= $rank;
}

if (isset($parameters['type'])) {
    // Only reachable over HTTP: getopt() drops --type= rather than passing ''.
    if (!is_string($parameters['type']) || trim($parameters['type']) === '') {
        fail('type must not be empty');
    }
    $type = strtolower(trim($parameters['type']));
    $filters[] = fn(Project $project) => strtolower($project->type) === $type;
}

// Paging is applied after filtering, so it walks the narrowed list.
$offset = isset($parameters['offset']) ? number($parameters['offset'], 'offset') : 0;
$limit = isset($parameters['limit']) ? number($parameters['limit'], 'limit') : null;

$projects = Portfolio::projects(function (Project $project) use ($filters) {
    foreach ($filters as $accepts) {
        if (!$accepts($project)) {
            return false;
        }
    }

    return true;
});

if (!cli()) {
    header('Content-Type: application/json; charset=utf-8');
}

echo json_encode(
    array_slice($projects, $offset, $limit),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
), "\n";
