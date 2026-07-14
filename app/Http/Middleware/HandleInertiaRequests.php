<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? $request->user() : null,
                'permissions' => $request->user() ? $request->user()->permissions() : null,
                'enable_permissions' => config('app.enable_permissions'),
            ],
            'media' => $this->mediaConfig(),
            'i18n' => $this->i18n($request),
        ];
    }

    /**
     * Locale, text direction, and the message catalog for the current area
     * (admin vs user, kept as separate namespaces). Consumed by the Svelte
     * `t()` helper and the `$dir` store.
     *
     * @return array{locale:string, dir:string, area:string, messages:array}
     */
    protected function i18n(Request $request): array
    {
        $locale = app()->getLocale();
        $rtlLocales = (array) config('app.rtl_locales', ['ar', 'fa', 'he', 'ur']);
        $area = $this->translationArea($request);

        return [
            'locale' => $locale,
            'dir' => in_array($locale, $rtlLocales, true) ? 'rtl' : 'ltr',
            'area' => $area,
            'messages' => $this->loadMessages($locale, $area),
        ];
    }

    /**
     * Which translation namespace applies to this request. Admin panel gets the
     * `admin/*` catalog; everything else the `user/*` catalog.
     */
    protected function translationArea(Request $request): string
    {
        $user = $request->user();

        if ($user && ($user->type ?? null) === 'admin') {
            return 'admin';
        }

        return $request->is('admin*', 'api/v1/admin*') ? 'admin' : 'user';
    }

    /**
     * Load and merge every PHP catalog under lang/{locale}/{area}/ into a nested
     * array keyed by filename (e.g. lang/en/admin/users.php -> ['users' => [...]]).
     * Falls back to the app fallback locale for any missing keys.
     *
     * @return array<string, mixed>
     */
    protected function loadMessages(string $locale, string $area): array
    {
        $read = function (string $loc) use ($area): array {
            $dir = lang_path("$loc/$area");
            if (! is_dir($dir)) {
                return [];
            }

            $messages = [];
            foreach (glob("$dir/*.php") as $file) {
                $messages[Str::before(basename($file), '.php')] = require $file;
            }

            return $messages;
        };

        $fallback = config('app.fallback_locale', 'en');
        $messages = $read($locale);

        if ($locale !== $fallback) {
            $messages = array_replace_recursive($read($fallback), $messages);
        }

        return $messages;
    }

    /**
     * Upload rules exposed to the frontend so file inputs and the media picker
     * can display the allowed size + extensions and set the input `accept`
     * filter. Single source of truth = config/media-library.php (env-driven).
     *
     * @return array<string, mixed>
     */
    protected function mediaConfig(): array
    {
        $maxFileSize = (int) config('media-library.max_file_size');
        $allowedTypes = config('media-library.allowed_types', []);

        // Per-type comma-joined ".ext" list, ready for <input accept="…">.
        $accept = [];
        foreach ($allowedTypes as $type => $extensions) {
            $accept[$type] = collect($extensions)
                ->map(fn ($ext) => '.' . ltrim(trim($ext), '.'))
                ->implode(',');
        }

        return [
            'max_file_size' => $maxFileSize,
            'max_file_size_human' => $this->humanFileSize($maxFileSize),
            'allowed_types' => $allowedTypes,
            'accept' => $accept,
        ];
    }

    /**
     * Format a byte count as a short human-readable string (e.g. "10 MB").
     */
    protected function humanFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        // Drop trailing ".0" for clean labels ("10 MB", not "10.0 MB").
        $formatted = rtrim(rtrim(number_format($value, 1), '0'), '.');

        return $formatted . ' ' . $units[$power];
    }
}
