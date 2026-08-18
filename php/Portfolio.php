<?php
/**
 * Central portfolio library.
 *
 * Reads the asset folders under portfolio/<year>/<project>/ and returns them as
 * Project instances. Nothing here prints, formats or takes CLI arguments — that
 * is the job of the small exporter scripts that include this file.
 */

// A script may define BASEDIR before requiring this file — see test.php.
defined('BASEDIR') || define('BASEDIR','/Volumes/archive/public/pike/portfolio');

/**
 * One project, built from the files in its folder.
 *
 * Text assets fill the matching property (description.txt -> $description); a
 * few are parsed on the way in (see setText). A text asset with no matching
 * property lands in $other, keyed by its name, so new asset types need no code
 * change to survive a read.
 */
class Project
{
    /** Properties that are derived from the folder, never from a .txt asset. */
    private const RESERVED = ['year', 'slug', 'path', 'images', 'files', 'preview', 'other'];

    public const DEFAULT_RANK = 50;
    public const DEFAULT_TYPE = 'project';

    public string $year = '';
    public string $slug = '';
    /** "<year>/<slug>", relative to portfolio/ */
    public string $path = '';

    /** Sort weight; DEFAULT_RANK when rank.txt is missing or not a number. */
    public int $rank = self::DEFAULT_RANK;
    /** DEFAULT_TYPE when type.txt is missing or empty. */
    public string $type = self::DEFAULT_TYPE;
    /** Falls back to the slug, ucfirst'ed. */
    public string $title = '';
    /** https:// is prepended when the asset omits a scheme. */
    public string $link = '';
    public string $owner = '';
    public string $description = '';
    /** Split on slashes, commas and newlines. */
    public array $roles = [];
    /** Split on slashes, commas and newlines. */
    public array $technologies = [];
    public string $design = '';
    public string $programming = '';
    public string $production = '';
    public string $content = '';

    /** Image paths, relative to portfolio/ */
    public array $images = [];
    /** All other non-text file paths, relative to portfolio/ */
    public array $files = [];
    /** The preview.* image, else the first image, else '' */
    public string $preview = '';

    /** Unrecognised text assets, as name => contents */
    public array $other = [];

    public function __construct(string $year, string $slug)
    {
        $this->year = $year;
        $this->slug = $slug;
        $this->path = $year . '/' . $slug;
    }

    /** Store a text asset on its own property, or in $other when there is none. */
    public function setText(string $name, string $value): void
    {
        if (!property_exists($this, $name) || in_array($name, self::RESERVED, true)) {
            $this->other[$name] = $value;
            return;
        }

        $this->$name = match ($name) {
            'rank' => is_numeric($value) ? (int) $value : self::DEFAULT_RANK,
            'link' => self::url($value),
            'roles', 'technologies' => self::list($value),
            default => $value,
        };
    }

    /** Fill in what the assets did not provide. Called once, after reading. */
    public function complete(): void
    {
        if ($this->title === '') {
            $this->title = ucfirst($this->slug);
        }
        if ($this->type === '') {
            $this->type = self::DEFAULT_TYPE;
        }
        if ($this->preview === '') {
            $this->preview = $this->images[0] ?? '';
        }
    }

    /** "design, frontend / copy" -> ['design', 'frontend', 'copy'] */
    private static function list(string $value): array
    {
        $items = array_map('trim', preg_split('#[/,\r\n]+#', $value));

        return array_values(array_filter($items, fn($item) => $item !== ''));
    }

    /** "example.com/x" -> "https://example.com/x"; an existing scheme is kept. */
    private static function url(string $value): string
    {
        if ($value === '' || preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
            return $value;
        }

        return 'https://' . ltrim($value, '/');
    }
}

class Portfolio
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];

    /** Absolute path of the asset root. */
    public static function dir(): string
    {
        if (BASEDIR) return BASEDIR;
        return dirname(__DIR__) . '/projects';
    }

    /**
     * All projects, newest year first, then by rank, then by slug.
     *
     * Pass a callback to filter, e.g.
     *   Portfolio::projects(fn(Project $p) => $p->year >= 2000);
     *
     * @return Project[]
     */
    public static function projects(?callable $filter = null): array
    {
        $projects = [];

        foreach (self::dirs(self::dir()) as $year) {
            if (!preg_match('/^\d{4}$/', $year)) {
                continue;
            }
            foreach (self::dirs(self::dir() . '/' . $year) as $slug) {
                $project = self::project($year, $slug);

                // Skip folders that yielded nothing: every property still at the
                // default a project read from no assets at all would have.
                $blank = new Project($year, $slug);
                $blank->complete();
                if ((array) $project === (array) $blank) {
                    continue;
                }

                $projects[] = $project;
            }
        }

        usort($projects, fn($a, $b) => [$b->year, $b->rank, $a->slug] <=> [$a->year, $a->rank, $b->slug]);

        if ($filter) {
            $projects = array_values(array_filter($projects, $filter));
        }

        return $projects;
    }

    /** One project, read from portfolio/<year>/<slug>/. */
    public static function project(string $year, string $slug): Project
    {
        $project = new Project($year, $slug);
        $folder = self::dir() . '/' . $project->path;

        foreach (self::files($folder) as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $relative = $project->path . '/' . $file;

            if ($extension === 'txt') {
                $project->setText(
                    self::name($file),
                    trim((string) file_get_contents($folder . '/' . $file))
                );
                continue;
            }

            if (in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                $project->images[] = $relative;
                if (pathinfo($file, PATHINFO_FILENAME) === 'preview') {
                    $project->preview = $relative;
                }
                continue;
            }

            $project->files[] = $relative;
        }

        $project->complete();

        return $project;
    }

    /** Property name for a text asset: "my notes.txt" -> "my_notes". */
    private static function name(string $file): string
    {
        $name = strtolower(pathinfo($file, PATHINFO_FILENAME));

        return trim(preg_replace('/[^a-z0-9]+/', '_', $name), '_');
    }

    /** Sorted subdirectory names; hidden and _underscored entries skipped. */
    private static function dirs(string $path): array
    {
        return array_values(array_filter(
            self::entries($path),
            fn($entry) => $entry[0] !== '_' && is_dir($path . '/' . $entry)
        ));
    }

    /** Sorted file names, hidden entries skipped. */
    private static function files(string $path): array
    {
        return array_values(array_filter(
            self::entries($path),
            fn($entry) => is_file($path . '/' . $entry)
        ));
    }

    private static function entries(string $path): array
    {
        $entries = is_dir($path) ? scandir($path) : false;
        if ($entries === false) {
            return [];
        }

        return array_values(array_filter($entries, fn($entry) => $entry[0] !== '.'));
    }
}
