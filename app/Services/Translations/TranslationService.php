<?php

namespace App\Services\Translations;

use App\Models\TranslationKey;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * The one place lang/{code}/{group}.php is read and written.
 *
 * Strings live on disk, never in the DB — translation_keys is the key registry
 * only. That split lets the Translations page paginate against the DB while
 * every value it renders comes from the file __() actually reads.
 *
 * A write is parse -> verify -> replace under one lock, so a partial file is
 * never visible. Singleton: load() memoises, so a page costs one read per group.
 */
class TranslationService
{
    /**
     * Loaded files, keyed "{code}:{group}".
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $loaded = [];

    public function __construct(protected Filesystem $files) {}

    /* -----------------------------------------
     Paths
    ------------------------------------------*/

    public function localePath(string $code): string
    {
        return lang_path($this->guard($code, config('translations.code_pattern'), 'locale'));
    }

    public function path(string $code, string $group): string
    {
        return $this->localePath($code)
            .DIRECTORY_SEPARATOR
            .$this->guard($group, config('translations.group_pattern'), 'group')
            .'.php';
    }

    /** Both become path segments, so anything off-pattern is refused here. */
    protected function guard(string $value, string $pattern, string $what): string
    {
        if (! preg_match($pattern, $value)) {
            throw new InvalidArgumentException("Invalid {$what} \"{$value}\".");
        }

        return $value;
    }

    /* -----------------------------------------
     Lifecycle (driven by LanguageObserver)
    ------------------------------------------*/

    /** Create lang/{code}/ and an empty common.php for a language that has none. */
    public function ensureLocale(string $code): void
    {
        $path = $this->localePath($code);

        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }

        foreach ($this->groups() as $group) {
            if (! $this->files->exists($this->path($code, $group))) {
                $this->write($code, $group, []);
            }
        }
    }

    /**
     * Follow a code rename — the files ARE the translations. A missing source or an
     * occupied target is left alone: this must never merge or clobber two locales.
     */
    public function renameLocale(string $from, string $to): void
    {
        $source = $this->localePath($from);
        $target = $this->localePath($to);

        if (! $this->files->isDirectory($source) || $this->files->exists($target)) {
            $this->ensureLocale($to);

            return;
        }

        $this->files->move($source, $target);

        $this->forget($from);
        $this->forget($to);

        $this->ensureLocale($to);
    }

    /* -----------------------------------------
     Reads
    ------------------------------------------*/

    /**
     * Every group the registry declares.
     *
     * An explicit whitelist rather than array_keys() of the seeder catalogue, so a
     * group can be retired without its lines disappearing from TranslationKeysSeeder.
     */
    public function groups(): array
    {
        return array_values((array) config('translations.groups', []));
    }

    /**
     * One file, memoised per request. A locale with no file reads as empty rather
     * than throwing — every key is simply untranslated.
     */
    public function load(string $code, string $group): array
    {
        $memo = "{$code}:{$group}";

        if (array_key_exists($memo, $this->loaded)) {
            return $this->loaded[$memo];
        }

        $path = $this->path($code, $group);

        if (! $this->files->exists($path)) {
            return $this->loaded[$memo] = [];
        }

        $lines = require $path;

        return $this->loaded[$memo] = is_array($lines) ? $lines : [];
    }

    public function get(string $code, string $group, string $key): ?string
    {
        $value = Arr::get($this->load($code, $group), $key);

        return is_string($value) ? $value : null;
    }

    /** A key counts as translated only when it holds a non-blank string. */
    public function isTranslated(string $code, string $group, string $key): bool
    {
        $value = $this->get($code, $group, $key);

        return $value !== null && trim($value) !== '';
    }

    /** Hang one locale's value on each row. One file read per group, via the memo. */
    public function hydrate(Collection $keys, string $code): void
    {
        $keys->each(fn (TranslationKey $key) => $key->withLine($code, $this->get($code, $key->group, $key->key)));
    }

    /**
     * Ids of the keys this locale has not translated. Resolved in PHP because the
     * values are in files, then handed back as an indexed whereIn so it composes
     * with search, group, sorting and pagination.
     */
    public function missingKeyIds(string $code): array
    {
        $ids = [];

        TranslationKey::query()
            ->select(['id', 'group', 'key'])
            ->orderBy('id')
            ->chunk(500, function (Collection $chunk) use ($code, &$ids) {
                foreach ($chunk as $key) {
                    if (! $this->isTranslated($code, $key->group, $key->key)) {
                        $ids[] = $key->id;
                    }
                }
            });

        return $ids;
    }

    /* -----------------------------------------
     The one write
    ------------------------------------------*/

    /**
     * Set one line, then rewrite the file. All four steps hold one exclusive lock,
     * so two admins editing different keys in a group cannot lose each other:
     *  1. re-read inside the lock (an earlier memo may be stale),
     *  2. apply the change,
     *  3. verify every registered key survives — missing ones are written as '',
     *  4. swap atomically and drop the memo.
     */
    public function put(string $code, string $group, string $key, ?string $value): void
    {
        $this->guard($code, config('translations.code_pattern'), 'locale');
        $this->guard($group, config('translations.group_pattern'), 'group');

        $this->withLock($code, $group, function () use ($code, $group, $key, $value) {
            $this->ensureLocale($code);
            $this->forget($code, $group);

            $lines = $this->load($code, $group);
            $old = $this->get($code, $group, $key);

            $this->assertNoScalarParent($lines, $key);

            Arr::set($lines, $key, (string) $value);

            if (config('translations.fill_missing_keys', true)) {
                foreach (TranslationKey::query()->where('group', $group)->pluck('key') as $registered) {
                    if (! Arr::has($lines, $registered)) {
                        $this->assertNoScalarParent($lines, $registered);

                        Arr::set($lines, $registered, '');
                    }
                }
            }

            $this->write($code, $group, $lines);
            $this->forget($code, $group);

            $this->log($code, $group, $key, $old, $value);
        });
    }

    /**
     * Drop lines from one locale's group file, and prune any parent left empty.
     *
     * THE COUNTERPART THIS APP WAS MISSING. Adding a key is "add it to
     * TranslationKeysSeeder::catalogue(), reseed"; removing one had no path at
     * all. Dropping the catalogue entry removes NOTHING from an install that has
     * already seeded — putMany() only ever writes — so a retired key kept
     * resolving through __() forever, and the registry row kept pointing at it.
     *
     * THE REGISTRY ROW IS THE CALLER'S JOB, and it must go too. With
     * config('translations.fill_missing_keys') false today nothing notices a
     * stale row; flip it true and the next admin edit to ANY key in the group
     * re-inserts the removed one as '' in every locale — and '' is present, so
     * Laravel's fallback never fires and every locale renders a blank instead of
     * English.
     *
     * PRUNING THE PARENT IS NOT TIDINESS. export() renders an empty array as
     * `[]`, so a group left holding `'subscribe' => []` makes __('site.x.subscribe')
     * return an array: @lang prints "Array" with a notice rather than failing
     * loudly. Anything that still has children is left alone.
     *
     * Writes no activity row: a removal is a code change arriving with a deploy,
     * not an admin edit, and log() would have no registered key to hang it off by
     * the time this runs.
     *
     * @param  array<int, string>  $keys  dotted keys, as registered
     * @return int  how many were actually present
     */
    public function remove(string $code, string $group, array $keys): int
    {
        $this->guard($code, config('translations.code_pattern'), 'locale');
        $this->guard($group, config('translations.group_pattern'), 'group');

        $removed = 0;

        $this->withLock($code, $group, function () use ($code, $group, $keys, &$removed) {
            $this->ensureLocale($code);
            $this->forget($code, $group);

            $lines = $this->load($code, $group);

            foreach ($keys as $key) {
                if (! Arr::has($lines, $key)) {
                    continue;
                }

                Arr::forget($lines, $key);
                $removed++;

                // Walk back up: 'a.b.c' leaves 'a.b' and then 'a' behind, and
                // either may now be an empty array.
                $parent = $key;

                while (str_contains($parent, '.')) {
                    $parent = Str::beforeLast($parent, '.');

                    if (Arr::get($lines, $parent) !== []) {
                        break;
                    }

                    Arr::forget($lines, $parent);
                }
            }

            if ($removed === 0) {
                return;
            }

            $this->write($code, $group, $lines);
            $this->forget($code, $group);
        });

        return $removed;
    }

    /**
     * Many lines in one locked write, for the seeder — put() would take a lock and
     * log an activity row per key.
     *
     * Writes no activity: seeding is not an admin edit, and put() stays the audited
     * path. $onlyMissing fills gaps without touching a translated line, which is
     * what makes reseeding safe.
     */
    public function putMany(string $code, string $group, array $values, bool $onlyMissing = true): int
    {
        $this->guard($code, config('translations.code_pattern'), 'locale');
        $this->guard($group, config('translations.group_pattern'), 'group');

        $written = 0;

        $this->withLock($code, $group, function () use ($code, $group, $values, $onlyMissing, &$written) {
            $this->ensureLocale($code);
            $this->forget($code, $group);

            $lines = $this->load($code, $group);

            foreach ($values as $key => $value) {
                if ($onlyMissing && Arr::has($lines, $key)) {
                    continue;
                }

                $this->assertNoScalarParent($lines, $key);

                Arr::set($lines, $key, (string) $value);
                $written++;
            }

            if ($written > 0) {
                $this->write($code, $group, $lines);
                $this->forget($code, $group);
            }
        });

        return $written;
    }

    /* -----------------------------------------
     Internals
    ------------------------------------------*/

    /**
     * Arr::set('a.b') where `a` is already a STRING replaces it with an array: the
     * sibling is gone and the file still parses. Refuse; the controller 422s.
     */
    protected function assertNoScalarParent(array $lines, string $key): void
    {
        $segments = explode('.', $key);
        array_pop($segments);

        $path = '';

        foreach ($segments as $segment) {
            $path = $path === '' ? $segment : "{$path}.{$segment}";

            if (Arr::has($lines, $path) && ! is_array(Arr::get($lines, $path))) {
                throw new InvalidArgumentException("The key \"{$path}\" already holds a translation, so \"{$key}\" cannot nest under it.");
            }
        }
    }

    /**
     * Render and replace. The temp file sits in the same directory so rename() stays
     * on one filesystem and is atomic — nobody can require() a half-written file.
     */
    protected function write(string $code, string $group, array $lines): void
    {
        $path = $this->path($code, $group);
        $source = "<?php\n\nreturn ".$this->export($lines).";\n";

        $temp = $path.'.'.Str::ulid().'.tmp';

        if ($this->files->put($temp, $source, true) === false) {
            throw new RuntimeException("Could not write {$temp}.");
        }

        if (! @rename($temp, $path)) {
            $this->files->delete($temp);

            throw new RuntimeException("Could not replace {$path}.");
        }

        // A lang file is a PHP file, so OPcache would serve the stale compiled copy.
        // Laravel has no lang cache and this app has none — this is the whole story.
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }

    /**
     * PHP source in the house style: short arrays, 4-space indent, trailing commas,
     * sorted per level. Not var_export(), which emits long syntax and fails Pint.
     */
    protected function export(array $lines, int $depth = 1): string
    {
        if ($lines === []) {
            return '[]';
        }

        ksort($lines);

        $pad = str_repeat('    ', $depth);
        $out = "[\n";

        foreach ($lines as $key => $value) {
            $out .= $pad.$this->quote((string) $key).' => ';
            $out .= is_array($value) ? $this->export($value, $depth + 1) : $this->quote((string) $value);
            $out .= ",\n";
        }

        return $out.str_repeat('    ', $depth - 1).']';
    }

    protected function quote(string $value): string
    {
        return "'".addcslashes($value, "'\\")."'";
    }

    /**
     * Serialise writers to one group of one locale. The lock is a SIDECAR: write()
     * replaces the target inode via rename(), so a lock on the target would guard a
     * path that no longer exists and the read-modify-write would still interleave.
     */
    protected function withLock(string $code, string $group, callable $callback): void
    {
        $directory = storage_path('framework/translations');

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $handle = fopen($directory.DIRECTORY_SEPARATOR."{$code}.{$group}.lock", 'c');

        if ($handle === false) {
            throw new RuntimeException("Could not open the translation lock for {$code}.{$group}.");
        }

        try {
            flock($handle, LOCK_EX);

            $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** Drop the memo for one group, or for a whole locale. */
    protected function forget(string $code, ?string $group = null): void
    {
        if ($group !== null) {
            unset($this->loaded["{$code}:{$group}"]);

            return;
        }

        foreach (array_keys($this->loaded) as $memo) {
            if (str_starts_with($memo, "{$code}:")) {
                unset($this->loaded[$memo]);
            }
        }
    }

    /**
     * Audit against the KEY, not the locale: "who last touched common.save" is the
     * question the page asks, and a per-key drawer can only filter on subject_id.
     * The locale rides along in meta.
     *
     * A file write has no model to observe, so this logs directly — the same
     * exception UploadService makes. Controllers still never call activity().
     */
    protected function log(string $code, string $group, string $key, ?string $old, ?string $value): void
    {
        $subject = TranslationKey::query()->where('group', $group)->where('key', $key)->first();

        // An unregistered key has no row to hang history off; the write already happened.
        if (! $subject) {
            return;
        }

        activity('translations')
            ->performedOn($subject)
            ->event('updated')
            ->withProperties([
                'old' => ['value' => $old],
                'attributes' => ['value' => $value],
                'meta' => ['name' => "{$group}.{$key}", 'locale' => $code],
            ])
            ->log('updated');
    }
}
