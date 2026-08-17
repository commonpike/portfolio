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
 * Run php/json.php as its own process, pointed at the fixtures with --basedir,
 * so its option parsing is exercised for real: getopt() reads the actual
 * command line.
 */
function json(string $arguments, string $stream = '2>/dev/null', string $basedir = BASEDIR): array
{
    $command = sprintf(
        'php %s --basedir=%s %s %s',
        escapeshellarg(ROOT . '/php/json.php'),
        escapeshellarg($basedir),
        $arguments,
        $stream
    );

    $lines = [];
    $status = 0;
    exec($command, $lines, $status);

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

/** The slugs in a cache file, which holds plain arrays rather than objects. */
function cached(string $file): array
{
    return array_column((array) json_decode((string) @file_get_contents($file), true), 'slug');
}

/** Where json.php caches this asset root, keyed the same way the exporter does. */
$cache = ROOT . '/php/cache/' . md5(realpath(BASEDIR)) . '.json';

/** Every fixture project, in the library's order: what an unfiltered run gives. */
$all = ['full', 'bad-rank', 'no-preview', 'sparse', 'rank-low'];

// Start cold, so a cache left by an earlier run cannot decide what is checked.
@unlink($cache);

section('Document');

check('output is valid JSON', json_decode(json('')['output']) !== null, true);
check('--basedir reads the root it is given', slugs(), $all);

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
// getopt() drops an option given without a value, so --type= never arrives here.
// Over HTTP ?type= does arrive, and is treated the same way: any type.
check('an empty --type selects everything', slugs('--type='), $all);
check('an empty --type is not an error', json('--type=')['status'], 0);
check('a whitespace --type selects everything', slugs('--type="  "'), $all);
check('a repeated --type is refused', json('--type=website --type=other')['status'], 1);
check('nothing is printed on stdout when refused', json('--rank=x')['output'], '');
check(
    'the reason and the usage go to stderr',
    json('--rank=x', '2>&1 1>/dev/null')['output'],
    "rank must be a whole number\nusage: php php/json.php [--basedir=<value>] [--rank=<value>] [--type=<value>] [--offset=<value>] [--limit=<value>] [--refresh]"
);
check('a --basedir that is not a directory is refused', json('', '2>/dev/null', ROOT . '/nope')['status'], 1);
check(
    'the reason names basedir',
    json('', '2>&1 1>/dev/null', ROOT . '/nope')['output'],
    "basedir must be a directory\nusage: php php/json.php [--basedir=<value>] [--rank=<value>] [--type=<value>] [--offset=<value>] [--limit=<value>] [--refresh]"
);
check('an unknown option is ignored', slugs('--bogus=1 --limit=1'), ['full']);

section('Cache');

@unlink($cache);
$narrowed = slugs('--rank=51');

check('a narrowed run caches the whole listing anyway', cached($cache), $all);
check('and still prints only what was asked for', $narrowed, ['full']);

// A tampered cache is the proof of where a listing came from: nothing in the
// fixtures can produce this project, so only the cache can.
file_put_contents($cache, json_encode([['slug' => 'from-cache']]));

check('a fresh cache is read instead of the asset root', slugs(), ['from-cache']);
check('--refresh rebuilds it', slugs('--refresh'), $all);
check('the rebuild is written back', cached($cache), $all);

file_put_contents($cache, json_encode([['slug' => 'from-cache']]));
touch($cache, time() - 86400); // a day old: past any TTL worth setting

check('a cache past its TTL is rebuilt', slugs(), $all);

$elsewhere = ROOT . '/php/cache/' . md5(realpath(ROOT . '/tests')) . '.json';
@unlink($elsewhere);
json('', '2>/dev/null', ROOT . '/tests'); // a real directory holding no years

check('another asset root caches separately', [is_file($elsewhere), cached($cache)], [true, $all]);

@unlink($elsewhere);
@unlink($cache); // leave nothing behind: the next run should start cold too

conclude();
