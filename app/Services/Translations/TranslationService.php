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
 * The one place lang/{code}/{group}.php files are read and written.
 *
 * Translated strings live on disk, never in the database — translation_keys
 * holds the key registry only. That split is what lets the Translations page
 * paginate against the DB (with search, sorting and filters) while every value
 * it renders comes out of the file that __() and @lang() actually read.
 *
 * A write is parse -> verify -> replace, all under one lock: load the file,
 * apply the change, confirm every registered key for that group survives, then
 * swap the file atomically. A partial file is never visible.
 *
 * Registered as a singleton — load() memoises per request, and a controller can
 * hydrate a whole page of rows with one read per group.
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

    /**
     * A code and a group both become path segments. Anything that doesn't match
     * its pattern is refused here rather than allowed to walk out of lang/.
     */
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
     * Follow a code rename: the files ARE the translations, so they move with it.
     * A missing source or an occupied target is left alone — this must never be
     * able to merge or clobber two locales.
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
     * Every group the registry declares (the lang files we manage).
     *
     * @return array<int, string>
     */
    public function groups(): array
    {
        return array_keys(config('translations.keys', []));
    }

    /**
     * The lines in one file, memoised for the request. A locale that has no file
     * yet reads as empty rather than throwing — every key is simply untranslated.
     *
     * @return array<string, mixed>
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

    /**
     * Hang one locale's value on every row of a paginated key page. One file read
     * per group thanks to load()'s memo, however many rows are on the page.
     *
     * @param  Collection<int, TranslationKey>  $keys
     */
    public function hydrate(Collection $keys, string $code): void
    {
        $keys->each(fn (TranslationKey $key) => $key->withLine($code, $this->get($code, $key->group, $key->key)));
    }

    /**
     * The ids of every registry key this locale has not translated. Resolved in
     * PHP because the values are in files, then handed back to the query builder
     * as a primary-key whereIn — an indexed IN composes with search, group,
     * sorting and pagination the way a computed column never could.
     *
     * @return array<int, string>
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
     * Set one line, then rewrite the whole file.
     *
     * Parse -> verify -> update, all inside one exclusive lock so two admins
     * editing different keys in the same group can't lose each other's work:
     *  1. re-read the file INSIDE the lock (a memo from earlier in the request
     *     may already be stale),
     *  2. apply the change,
     *  3. verify every registered key for the group survives — anything missing
     *     is written back as '' rather than silently dropped,
     *  4. swap the file atomically and drop the memo.
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
     * Set many lines in one locked write. Used by the seeder, which would
     * otherwise take a lock, rewrite the file and log an activity row per key.
     *
     * Seeding is not an admin edit, so this deliberately writes no activity —
     * put() remains the audited path. With $onlyMissing it fills gaps without
     * ever touching a line someone has already translated, which is what makes
     * reseeding safe.
     *
     * @param  array<string, string>  $values  dotted key => value
     * @return int how many lines were actually written
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
     * Arr::set('a.b') on lines where `a` is already a STRING replaces that string
     * with an array — the sibling key is gone and the file still parses. Refuse
     * instead; the controller turns this into a 422.
     *
     * @param  array<string, mixed>  $lines
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
     * Render and replace the file.
     *
     * The temp file sits in the same directory so rename() stays on one
     * filesystem and is atomic — a concurrent request must never be able to
     * require() a half-written file.
     *
     * @param  array<string, mixed>  $lines
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

        // A lang file IS a PHP file, so OPcache would keep serving the previous
        // compiled copy. Laravel has no lang cache and this app has no
        // application cache — this is the whole invalidation story.
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }

    /**
     * Render lines as PHP source in the house style: short arrays, 4-space
     * indent, trailing commas, sorted at each level so diffs stay readable.
     * Not var_export(), which emits `array (` long syntax and fails Pint.
     *
     * @param  array<string, mixed>  $lines
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
     * Serialise writers to one group of one locale.
     *
     * The lock is a sidecar, not the target file: write() replaces the target's
     * inode via rename(), so a lock held on it would guard a file that no longer
     * exists at that path and the read-modify-write would still interleave.
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
     * Audit the edit against the KEY, not the locale: "who last touched
     * common.save, and what did it say before" is the question the Translations
     * page asks, and a per-key drawer can only filter on subject_id. The locale
     * rides along in meta so one key's history reads across every language.
     *
     * A file write has no model to observe, so this logs directly — the same
     * exception UploadService makes for attach/detach. Controllers still never
     * call activity().
     */
    protected function log(string $code, string $group, string $key, ?string $old, ?string $value): void
    {
        $subject = TranslationKey::query()->where('group', $group)->where('key', $key)->first();

        // An unregistered key has no row to hang the history off. The write
        // itself already happened and is not worth failing over.
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
