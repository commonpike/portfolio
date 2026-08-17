<?php
/**
 * Exporter: the portfolio as Markdown, grouped by year.
 *
 *   php php/markdown.php > portfolio.md            every project
 *   php php/markdown.php --rank=60 > short.md      only projects ranked 60 or higher
 *   php php/markdown.php --basedir=/mnt/other      read another asset root
 */

/** Accepted long options; a trailing ':' means the option takes a value. */
const OPTIONS = ['basedir:', 'rank:'];

function usage(): never
{
    $usage = implode('] [--', array_map(
        fn($option) => rtrim($option, ':') . '=<value>',
        OPTIONS
    ));

    fwrite(STDERR, "usage: php php/markdown.php [--$usage]\n");
    exit(1);
}

/** A "- label: value" bullet; '' when there is nothing to show. */
function bullet(string $label, $value): string
{
    $value = is_array($value) ? implode(', ', $value) : trim($value);

    if ($value === '') {
        return '';
    }

    return $label === '' ? "- $value" : "- $label: $value";
}

/** A blockquote, keeping the original line breaks. */
function quote(string $text): string
{
    $lines = array_map(fn($line) => rtrim('> ' . $line), preg_split('/\R/', trim($text)));

    return implode("\n", $lines);
}

/** Groups of lines, blank-separated, with empty groups dropped. */
function blocks(array $groups): string
{
    $groups = array_map(
        fn($group) => implode("\n", array_filter((array) $group, fn($line) => $line !== '')),
        $groups
    );

    return implode("\n\n", array_filter($groups, fn($group) => $group !== ''));
}

$options = getopt('', OPTIONS);

if ($options === false) {
    usage();
}

// --basedir has to be settled before the library is required, because that is
// where BASEDIR gets its default. A wrapper that defined it already wins, as it
// does everywhere else.
if (isset($options['basedir'])) {
    if (!is_string($options['basedir']) || !is_dir($options['basedir'])) {
        usage();
    }
    defined('BASEDIR') || define('BASEDIR', realpath($options['basedir']));
}

require_once __DIR__ . '/Portfolio.php';

// One entry per option that narrows the selection; a project must pass them all.
$filters = [];

if (isset($options['rank'])) {
    if (!is_numeric($options['rank'])) {
        usage();
    }
    $minimum = (int) $options['rank'];
    $filters[] = fn(Project $project) => $project->rank >= $minimum;
}

$projects = Portfolio::projects(function (Project $project) use ($filters) {
    foreach ($filters as $accepts) {
        if (!$accepts($project)) {
            return false;
        }
    }

    return true;
});

$year = '';

foreach ($projects as $project) {
    if ($project->year !== $year) {
        $year = $project->year;
        echo "## $year\n\n";
    }

    echo blocks([
        "### $project->title",
        [
            bullet('', $project->link),
            bullet('Project owner', $project->owner),
            bullet('Technologies', $project->technologies),
        ],
        $project->description === '' ? '' : quote($project->description),
        [
            bullet('role', $project->roles),
            bullet('ICW', array_filter([
                $project->design,
                $project->programming,
                $project->production,
                $project->content,
            ])),
        ],
    ]), "\n\n";
}
