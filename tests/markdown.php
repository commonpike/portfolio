<?php
/**
 * Tester for markdown.php: the rendered document and the command line.
 *
 *   php tests/markdown.php
 *
 * Only formatting is checked here — how assets become properties is
 * tests/Portfolio.php's business.
 */

require_once __DIR__ . '/common.php';

section('Document');

ob_start();
require ROOT . '/markdown.php';
$markdown = ob_get_clean();

// The whole document, since the fixture root holds only fixtures. Every project
// block ends with a blank line, hence the trailing newline after the heredoc.
check('markdown output', $markdown, <<<'MARKDOWN'
## 2099

### Full Example

- https://example.com/full?a=1
- Project owner: Acme BV
- Technologies: php, mysql, javascript, css

> First line.
>
> Second paragraph.

- role: design, frontend, copy, strategy
- ICW: Ada L., Grace H., Studio X, Editors Inc

### Bad-rank

> Rank asset is not a number.

### No-preview

> No preview image here.

### Sparse

> Only a description here.

### Rank-low

- http://example.org/kept

MARKDOWN . "\n");

section('Command line');

/**
 * Run markdown.php as its own process, against the fixtures, so its option
 * parsing is exercised for real: getopt() reads the actual command line.
 */
function markdown(string $arguments): array
{
    $code = sprintf(
        'define("BASEDIR", %s); require %s;',
        var_export(BASEDIR, true),
        var_export(ROOT . '/markdown.php', true)
    );

    $lines = [];
    $status = 0;
    exec('php -r ' . escapeshellarg($code) . ' -- ' . $arguments . ' 2>/dev/null', $lines, $status);

    return ['status' => $status, 'headings' => array_values(array_filter(
        $lines,
        fn($line) => str_starts_with($line, '### ')
    ))];
}

$shortlist = markdown('--rank=51');

check('--rank drops lower-ranked projects', $shortlist['headings'], ['### Full Example']);
check('--rank exits 0', $shortlist['status'], 0);
check('a non-numeric --rank is refused', markdown('--rank=abc'), ['status' => 1, 'headings' => []]);

conclude();
