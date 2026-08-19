<?php
/**
 * Tester for php/Portfolio.php: which folders are listed, and how assets become
 * properties.
 *
 *   php tests/Portfolio.php
 *
 * The fixtures cover every parsed property, the credit fields, an unreadable
 * rank, an empty type, a scheme-less and a scheme'd link, unrecognised text
 * assets, images versus other files, both preview outcomes, and every reason a
 * folder is skipped. Add a case by adding a folder under tests/fixtures/2099/ and
 * an assertion here.
 */

require_once __DIR__ . '/common.php';

section('Selection');

// The fixture root holds nothing but fixtures, so this is the whole listing:
// _draft, @eaDir, empty and ignored-only are absent because they are skipped.
check(
    'listed, ranked high to low with slug as tie-break',
    array_map(fn(Project $project) => $project->slug, Portfolio::projects()),
    ['full', 'bad-rank', 'no-preview', 'sparse', 'rank-low']
);
check(
    'every listed project comes from the one readable year',
    array_unique(array_map(fn(Project $project) => $project->year, Portfolio::projects())),
    ['2099']
);
check(
    'a specific project is readable even when skipped in listings',
    Portfolio::project('2099', '_draft')->description,
    'Parked work in progress.'
);
check(
    'an @-prefixed folder holds a readable asset, so its absence above is the rule',
    Portfolio::project('2099', '@eaDir')->description,
    'A folder belonging to the volume, not to the collection.'
);
check(
    'a filter narrows the listing',
    array_map(fn(Project $project) => $project->slug, Portfolio::projects(
        fn(Project $project) => $project->rank >= 51
    )),
    ['full']
);

section('Properties');

$full = Portfolio::project('2099', 'full');

check('year from the parent folder', $full->year, '2099');
check('slug from the folder', $full->slug, 'full');
check('path', $full->path, '2099/full');
check('rank', $full->rank, 80);
check('type', $full->type, 'Website');
check('title from title.txt', $full->title, 'Full Example');
check('link gains a scheme', $full->link, 'https://example.com/full?a=1');
check('link keeps its own scheme', Portfolio::project('2099', 'rank-low')->link, 'http://example.org/kept');
check('owner', $full->owner, 'Acme BV');
check('description keeps its line breaks', $full->description, "First line.\n\nSecond paragraph.");
check('roles split and trimmed', $full->roles, ['design', 'frontend', 'copy', 'strategy']);
check('technologies split and trimmed', $full->technologies, ['php', 'mysql', 'javascript', 'css']);
check('design', $full->design, 'Ada L.');
check('programming', $full->programming, 'Grace H.');
check('production', $full->production, 'Studio X');
check('content', $full->content, 'Editors Inc');

section('Defaults and fallbacks');

$sparse = Portfolio::project('2099', 'sparse');

$badRank = Portfolio::project('2099', 'bad-rank');

check('rank falls back when the asset is not numeric', $badRank->rank, Project::DEFAULT_RANK);
check('rank falls back when the asset is missing', $sparse->rank, Project::DEFAULT_RANK);
check('type falls back when the asset is missing', $sparse->type, Project::DEFAULT_TYPE);
check('type falls back when the asset is empty', $badRank->type, Project::DEFAULT_TYPE);
check('title falls back to the slug', $sparse->title, 'Sparse');
check('missing string is empty, never null', $sparse->owner, '');
check('missing array is empty', $sparse->roles, []);
check('preview is empty without images', $sparse->preview, '');

section('Files');

check('images, relative to the asset root', $full->images, ['2099/full/extra.png', '2099/full/preview.jpg']);
check('preview prefers preview.*', $full->preview, '2099/full/preview.jpg');
check('preview falls back to the first image', Portfolio::project('2099', 'no-preview')->preview, '2099/no-preview/shot.png');
check('non-image, non-text goes to files', $full->files, ['2099/full/spec.pdf']);
check(
    'unrecognised text lands in other, reserved names included',
    $full->other,
    ['preview' => 'not an image', 'random_note' => 'a note']
);

conclude();
