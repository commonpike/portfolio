<?php
/**
 * Shared setup and helpers for the testers in this folder. Runs no checks.
 *
 * Points the library at tests/fixtures/ — an asset root of its own, so a tester
 * never reads the live archive and no fixture can leak into a real export.
 */

/** The project root, one level up from tests/. */
const ROOT = __DIR__ . '/..';

define('BASEDIR', __DIR__ . '/fixtures');
require_once ROOT . '/Portfolio.php';

$failures = 0;

/** A heading above a group of checks. */
function section(string $title): void
{
    echo "$title\n";
}

/** Compare and report; arrays and scalars both welcome. */
function check(string $what, $actual, $expected): void
{
    global $failures;

    if ($actual === $expected) {
        echo "  ok   $what\n";
        return;
    }

    $failures++;
    echo "  FAIL $what\n";
    echo "       expected: ", json_encode($expected), "\n";
    echo "       actual:   ", json_encode($actual), "\n";
}

/** Summarise and exit 1 if anything failed, so testers work as a commit check. */
function conclude(): never
{
    global $failures;

    echo $failures === 0 ? "\nAll checks passed.\n" : "\n$failures check(s) failed.\n";

    exit($failures === 0 ? 0 : 1);
}
