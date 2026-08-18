<?php
/**
 * Importer: a CSV of projects into asset folders under the asset root.
 *
 *   php php/import.php --basedir=projects            import into the local root
 *   php php/import.php --csv=exports/other.csv       read another listing
 *   php php/import.php --basedir=projects --dry-run  report without writing
 *   php php/import.php --basedir=projects --force    overwrite existing folders
 *
 * The reverse of the exporters: they format what the library read, this writes
 * what the library will read. One row becomes <year>/<slug>/, one column becomes
 * one <property>.txt in it — so a column added to the CSV needs no change here,
 * and lands in Project::$other until it is declared.
 *
 * A folder that already exists is left alone unless --force, which then makes it
 * match the row: the columns' own .txt files are rewritten or removed, and
 * everything else in the folder — images, assets the CSV has no column for — is
 * kept. Nothing here reads the assets; that is the library's job.
 */

/** Accepted long options; a trailing ':' means the option takes a value. */
const OPTIONS = ['basedir:', 'csv:', 'dry-run', 'force'];

/** The two columns that name the folders instead of becoming a .txt. */
const FOLDER_COLUMNS = ['yyyy', 'slug'];

function usage(): never
{
    $usage = implode('] [--', array_map(
        fn($option) => str_ends_with($option, ':') ? rtrim($option, ':') . '=<value>' : $option,
        OPTIONS
    ));

    fwrite(STDERR, "usage: php php/import.php [--$usage]\n");
    exit(1);
}

/**
 * Rows of the CSV, as line number => column => value, blank rows dropped.
 *
 * Values are trimmed and their line endings normalised, the way the library
 * trims what it reads, so a round trip through the files changes nothing.
 */
function rows(string $file): array
{
    $raw = (string) file_get_contents($file);
    $handle = fopen($file, 'r');
    $header = array_map('name', (array) fgetcsv($handle, 0, ',', '"', '\\'));
    $line = 1;
    $rows = [];

    while (true) {
        $before = ftell($handle);
        $row = fgetcsv($handle, 0, ',', '"', '\\');
        if ($row === false) {
            break;
        }

        // Track the physical line, which a quoted multi-line field runs past.
        $at = $line + 1;
        $line += substr_count(substr($raw, $before, ftell($handle) - $before), "\n");

        $row = array_map(fn($value) => trim(str_replace("\r\n", "\n", (string) $value)), (array) $row);
        if (implode('', $row) === '') {
            continue;
        }

        $rows[$at] = array_combine($header, array_pad($row, count($header), ''));
    }

    return $rows;
}

/** Asset name for a column: "annual report" -> "annual_report", as the library reads it back. */
function name(string $column): string
{
    return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($column))), '_');
}

/** Why this row cannot become a folder, or '' when it can. */
function refusal(array $row, string $folder, bool $force): string
{
    if (!preg_match('/^\d{4}$/', $row['yyyy'] ?? '')) {
        return "year \"{$row['yyyy']}\" is not four digits";
    }
    // Anything the library would not read back as the same slug is refused,
    // rather than quietly landing under a name the CSV does not hold.
    if (!preg_match('/^_?[a-z0-9][a-z0-9._-]*$/', $row['slug'] ?? '')) {
        return "slug \"{$row['slug']}\" is not a safe folder name";
    }
    if (is_dir($folder) && !$force) {
        return "{$row['yyyy']}/{$row['slug']} exists — use --force to overwrite";
    }

    return '';
}

/** Write one project's assets; returns the number of files written. */
function write(string $folder, array $row): int
{
    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        fwrite(STDERR, "cannot create $folder\n");
        exit(1);
    }

    $written = 0;

    foreach ($row as $column => $value) {
        if (in_array($column, FOLDER_COLUMNS, true)) {
            continue;
        }

        // The folder is made to match the row: an asset this column wrote on an
        // earlier run goes, so emptying a column empties the property. Only the
        // columns' own .txt files are touched — images and text assets the CSV
        // knows nothing about are left where they are.
        if (is_file("$folder/$column.txt")) {
            unlink("$folder/$column.txt");
        }

        // An empty column writes nothing: a missing asset leaves the default,
        // which is what an empty one would read as anyway.
        if ($value === '') {
            continue;
        }

        // No trailing newline, matching the assets already on disk.
        if (file_put_contents("$folder/$column.txt", $value) === false) {
            fwrite(STDERR, "cannot write $folder/$column.txt\n");
            exit(1);
        }
        $written++;
    }

    return $written;
}

$options = getopt('', OPTIONS);

if ($options === false) {
    usage();
}

// --basedir has to be settled before the library is required, because that is
// where BASEDIR gets its default. A wrapper that defined it already wins, as it
// does everywhere else. Note that here it is where the assets are *written*.
if (isset($options['basedir'])) {
    if (!is_string($options['basedir']) || !is_dir($options['basedir'])) {
        usage();
    }
    defined('BASEDIR') || define('BASEDIR', realpath($options['basedir']));
}

require_once __DIR__ . '/Portfolio.php';

$csv = $options['csv'] ?? dirname(__DIR__) . '/exports/portfolio.csv';

if (!is_string($csv) || !is_file($csv)) {
    usage();
}

$dry = array_key_exists('dry-run', $options);
$force = array_key_exists('force', $options);

$rows = rows($csv);
$folders = 0;
$written = 0;
$refused = [];

foreach ($rows as $at => $row) {
    $folder = Portfolio::dir() . '/' . ($row['yyyy'] ?? '') . '/' . ($row['slug'] ?? '');

    $refusal = refusal($row, $folder, $force);
    if ($refusal !== '') {
        $refused[] = "line $at: $refusal";
        continue;
    }

    $folders++;
    $written += $dry
        ? count(array_filter(
            $row,
            fn($value, $column) => $value !== '' && !in_array($column, FOLDER_COLUMNS, true),
            ARRAY_FILTER_USE_BOTH
        ))
        : write($folder, $row);
}

printf(
    "%s%d rows, %d folders, %d text assets -> %s\n",
    $dry ? '[dry run] ' : '',
    count($rows),
    $folders,
    $written,
    Portfolio::dir()
);

foreach ($refused as $refusal) {
    fwrite(STDERR, "skipped $refusal\n");
}

exit($refused === [] ? 0 : 1);
