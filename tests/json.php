<?php
/**
 * Tester for php/json.php: the serialised document and the command line.
 *
 *   php tests/json.php
 *
 * Only the CLI is exercised. The HTTP path shares everything but parameters(),
 * fail() and the Content-Type header, and testing it would mean starting a web
 * server — which cannot be relied on to be possible here.
 *
 * How assets become properties is tests/Portfolio.php's business.
 */

require_once __DIR__ . '/common.php';

/**
 * Run php/json.php as its own process, against the fixtures, so its option parsing
 * is exercised for real: getopt() reads the actual command line.
 */
function json(string $arguments, string $stream = '2>/dev/null'): array
{
    $code = sprintf(
        'define("BASEDIR", %s); require %s;',
        var_export(BASEDIR, true),
        var_export(ROOT . '/php/json.php', true)
    );

    $lines = [];
    $status = 0;
    exec('php -r ' . escapeshellarg($code) . ' -- ' . $arguments . ' ' . $stream, $lines, $status);

    return ['status' => $status, 'output' => implode("\n", $lines)];
}

/** The projects json.php printed, decoded. */
function projects(string $arguments = ''): array
{
    $decoded = json_decode(json($arguments)['output']);

    return is_array($decoded) ? $decoded : [];
}

/** Their slugs, in the order printed. */
function slugs(string $arguments = ''): array
{
    return array_map(fn($project) => $project->slug, projects($arguments));
}

section('Document');

check('output is valid JSON', json_decode(json('')['output']) !== null, true);
check('every project, in the library\'s order', slugs(), ['full', 'bad-rank', 'no-preview', 'sparse', 'rank-low']);

$full = projects('--limit=1')[0] ?? null;

check(
    'every property is exported',
    $full === null ? [] : array_keys(get_object_vars($full)),
    [
        'year', 'slug', 'path', 'rank', 'type', 'title', 'link', 'owner',
        'description', 'roles', 'technologies', 'design', 'programming',
        'production', 'content', 'images', 'files', 'preview', 'other',
    ]
);
check('a string property stays a string', $full->year ?? null, '2099');
check('rank is a number, not a string', $full->rank ?? null, 80);
check('a list property stays a list', $full->roles ?? null, ['design', 'frontend', 'copy', 'strategy']);
check('paths are not escaped', $full->preview ?? null, '2099/full/preview.jpg');
check('description keeps its line breaks', $full->description ?? null, "First line.\n\nSecond paragraph.");
check('other is exported as an object', (array) ($full->other ?? null), ['preview' => 'not an image', 'random_note' => 'a note']);

section('Selecting');

check('--rank keeps the equal and higher ranked', slugs('--rank=50'), ['full', 'bad-rank', 'no-preview', 'sparse']);
check('--rank drops the lower ranked', slugs('--rank=51'), ['full']);
check('--type matches', slugs('--type=Website'), ['full']);
check('--type ignores case', slugs('--type=website'), ['full']);
check('--type without a match yields nothing', slugs('--type=nope'), []);
check('filters combine', slugs('--type=website --rank=51'), ['full']);
check('a contradiction yields nothing', slugs('--type=website --rank=81'), []);

section('Paging');

check('--offset skips', slugs('--offset=1'), ['bad-rank', 'no-preview', 'sparse', 'rank-low']);
check('--limit truncates', slugs('--limit=2'), ['full', 'bad-rank']);
check('--offset with --limit', slugs('--offset=1 --limit=2'), ['bad-rank', 'no-preview']);
check('--limit=0 yields nothing', slugs('--limit=0'), []);
check('an offset past the end yields nothing', slugs('--offset=99'), []);
check('paging walks the filtered list, not the whole one', slugs('--rank=50 --offset=3'), ['sparse']);

section('Bad input');

check('a non-numeric --rank is refused', json('--rank=x')['status'], 1);
check('a non-numeric --limit is refused', json('--limit=abc')['status'], 1);
check('a negative --offset is refused', json('--offset=-1')['status'], 1);
check('a repeated option is refused', json('--rank=1 --rank=2')['status'], 1);
// getopt() drops an option given without a value, so --type= never arrives and
// cannot be refused. Over HTTP ?type= does arrive, and fail()s as empty.
check('an empty --type is dropped, selecting everything', slugs('--type='), ['full', 'bad-rank', 'no-preview', 'sparse', 'rank-low']);
check('an empty --type is not an error', json('--type=')['status'], 0);
check('nothing is printed on stdout when refused', json('--rank=x')['output'], '');
check(
    'the reason and the usage go to stderr',
    json('--rank=x', '2>&1 1>/dev/null')['output'],
    "rank must be a whole number\nusage: php php/json.php [--rank=<value>] [--type=<value>] [--offset=<value>] [--limit=<value>]"
);
check('an unknown option is ignored', slugs('--bogus=1 --limit=1'), ['full']);

conclude();
