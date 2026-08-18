<?php
/**
 * Tester for php/import.php: which folders and text assets a CSV becomes.
 *
 *   php tests/import.php
 *
 * Both the CSV it reads and the asset root it writes are built under a temporary
 * folder of this tester's own, so the run never touches tests/fixtures/ — which
 * is an asset root the other testers assert over in full — nor the real archive.
 *
 * How assets become properties is tests/Portfolio.php's business; the one check
 * here that reads assets back does it through php/json.php, to prove the library
 * finds what the importer wrote.
 */

require_once __DIR__ . '/common.php';

/** The temporary asset root the importer writes into. */
$root = sys_get_temp_dir() . '/portfolio-import-test';

/** The CSV it reads. */
$csv = $root . '.csv';

/** Remove a folder and everything in it. */
function clear(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    foreach (array_diff((array) scandir($path), ['.', '..']) as $entry) {
        $child = "$path/$entry";
        is_dir($child) ? clear($child) : unlink($child);
    }

    rmdir($path);
}

/**
 * Run php/import.php as its own process, so getopt() reads a real command line.
 *
 * @return array{status:int, output:string}
 */
function import(string $arguments = '', string $stream = '2>&1'): array
{
    global $root, $csv;

    $command = sprintf(
        'php %s --basedir=%s --csv=%s %s %s',
        escapeshellarg(ROOT . '/php/import.php'),
        escapeshellarg($root),
        escapeshellarg($csv),
        $arguments,
        $stream
    );

    $lines = [];
    $status = 0;
    exec($command, $lines, $status);

    return ['status' => $status, 'output' => implode("\n", $lines)];
}

/** The text assets in a project folder, as name => contents. */
function assets(string $path): array
{
    global $root;

    $folder = "$root/$path";
    $files = is_dir($folder) ? array_diff((array) scandir($folder), ['.', '..']) : [];
    $assets = [];

    foreach ($files as $file) {
        $assets[$file] = (string) file_get_contents("$folder/$file");
    }

    return $assets;
}

/**
 * Slugs php/json.php lists from the imported root, newest first.
 *
 * --refresh, because json.php caches a listing per asset root for CACHE_TTL
 * seconds and this root keeps its name between runs: a cache left by an earlier
 * run would otherwise answer for the folders this one just wrote.
 */
function imported(): array
{
    global $root;

    $command = sprintf(
        'php %s --basedir=%s --refresh 2>/dev/null',
        escapeshellarg(ROOT . '/php/json.php'),
        escapeshellarg($root)
    );

    $lines = [];
    exec($command, $lines);

    return array_column((array) json_decode(implode("\n", $lines), true), 'slug');
}

/** Write the CSV this run imports. */
function csv(array $rows): void
{
    global $csv;

    $handle = fopen($csv, 'w');
    foreach ($rows as $row) {
        fputcsv($handle, $row, ',', '"', '\\');
    }
    fclose($handle);
}

// The columns of the test CSV: the two that name folders, a parsed property, a
// plain one, a property left empty throughout, and one the library has no
// property for.
const HEADER = ['yyyy', 'slug', 'rank', 'title', 'link', 'description', 'content', 'awards'];

/** A row of HEADER, with only what a check cares about filled in. */
function row(array $values): array
{
    return array_map(fn($column) => $values[$column] ?? '', HEADER);
}

clear($root);
@unlink($csv);
mkdir($root, 0755, true);

csv([
    HEADER,
    array_fill(0, count(HEADER), ''),
    row(['yyyy' => '2099', 'slug' => 'full', 'rank' => '80', 'title' => 'Full',
         'link' => 'example.com/x', 'description' => "First line\n\nSecond line",
         'awards' => 'A prize']),
    row(['yyyy' => '2099', 'slug' => 'sparse', 'title' => 'A sparse project']),
    row(['yyyy' => '2099', 'slug' => 'blank', 'title' => 'Blank']),
    row(['yyyy' => '2099', 'slug' => '_parked', 'title' => 'Parked']),
    row(['yyyy' => '20x9', 'slug' => 'bad-year', 'title' => 'Bad year']),
    row(['yyyy' => '2099', 'slug' => 'bad slug', 'title' => 'Bad slug']),
]);

section('A dry run reports but writes nothing');
$run = import('--dry-run');
check('reports the folders it would write', str_contains($run['output'], '4 folders'), true);
check('reports the assets it would write', str_contains($run['output'], '8 text assets'), true);
check('wrote no folder', assets('2099/full'), []);

section('Rows become folders of text assets');
$run = import();
check('the two refusals are reported', substr_count($run['output'], 'skipped line'), 2);
// The line numbers run past the row count: the first row's description is a
// quoted field spanning three lines of the file.
check('the bad year names its line', str_contains($run['output'], 'line 9'), true);
check('the unsafe slug names its line', str_contains($run['output'], 'line 10'), true);
check('exit status is 1 when a row was refused', $run['status'], 1);
check('a refused row wrote no folder', is_dir("$root/2099/bad slug"), false);
check('a refused year wrote no folder', is_dir("$root/20x9"), false);

section('One column, one .txt — and only for what the row holds');
check('a filled row', array_keys(assets('2099/full')), [
    'awards.txt', 'description.txt', 'link.txt', 'rank.txt', 'title.txt',
]);
check('an empty column writes no asset', array_keys(assets('2099/sparse')), ['title.txt']);
check('a row holding nothing but a slug-shaped title', array_keys(assets('2099/blank')), ['title.txt']);
check('the value is written verbatim, unterminated', assets('2099/full')['rank.txt'], '80');
check('a link keeps the form the CSV holds', assets('2099/full')['link.txt'], 'example.com/x');
check('a multi-line value keeps its breaks', assets('2099/full')['description.txt'], "First line\n\nSecond line");
check('a column the library has no property for is still written', assets('2099/full')['awards.txt'], 'A prize');
check('an underscored slug is written as it stands', array_keys(assets('2099/_parked')), ['title.txt']);

section('The library reads back what was written');
// 2099/blank is written but not listed, and rightly so: its title.txt holds
// exactly the ucfirst'ed slug the library falls back to, so the folder is
// indistinguishable from one that held nothing at all. A row needs one value
// the defaults do not already supply to make the listing.
check('the listing, parked and blank projects skipped', imported(), ['full', 'sparse']);

section('An existing folder is kept unless --force');
file_put_contents("$root/2099/full/preview.jpg", 'not really an image');
file_put_contents("$root/2099/full/notes.txt", 'kept');
csv([
    HEADER,
    row(['yyyy' => '2099', 'slug' => 'full', 'rank' => '40', 'title' => 'Full']),
]);

$run = import();
check('the existing folder is reported, not written', str_contains($run['output'], '2099/full exists'), true);
check('exit status is 1', $run['status'], 1);
check('the assets are untouched', assets('2099/full')['rank.txt'], '80');

$run = import('--force');
check('--force rewrites a changed column', assets('2099/full')['rank.txt'], '40');
check('--force removes the asset of an emptied column', isset(assets('2099/full')['link.txt']), false);
check('--force keeps a text asset the CSV has no column for', assets('2099/full')['notes.txt'] ?? '', 'kept');
check('--force keeps an image', isset(assets('2099/full')['preview.jpg']), true);
check('exit status is 0 when nothing was refused', $run['status'], 0);

section('Bad option values are refused with usage');
check('a basedir that is not a directory', import('', '2>&1')['status'], 1);
$command = sprintf('php %s --basedir=%s 2>&1', escapeshellarg(ROOT . '/php/import.php'), escapeshellarg("$root/nope"));
exec($command, $lines, $status);
check('  reports usage', str_contains(implode('', $lines), 'usage: php php/import.php'), true);
check('  exits 1', $status, 1);

$command = sprintf(
    'php %s --basedir=%s --csv=%s 2>&1',
    escapeshellarg(ROOT . '/php/import.php'),
    escapeshellarg($root),
    escapeshellarg("$root/nope.csv")
);
exec($command, $lines, $status);
check('a csv that is not a file exits 1', $status, 1);

// The listing check above went through json.php, which caches per asset root;
// leave no cache of a temporary root behind.
@unlink(ROOT . '/php/cache/' . md5((string) realpath($root)) . '.json');

clear($root);
@unlink($csv);

conclude();
