<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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
                // Seeds the bell badge on first paint; kept fresh by the bell's poll.
                'unread_notifications' => $request->user() ? $request->user()->unreadNotificationsCount() : 0,
            ],
            'media' => $this->mediaConfig(),
        ];
    }

    /**
     * Upload rules exposed to the frontend so file inputs and the media picker
     * can display the allowed size + extensions and set the input `accept`
     * filter. Single source of truth = config/uploads.php (env-driven).
     *
     * @return array<string, mixed>
     */
    protected function mediaConfig(): array
    {
        $maxFileSize = (int) config('uploads.max_file_size');
        $allowedTypes = config('uploads.allowed_types', []);

        // Per-type comma-joined ".ext" list, ready for <input accept="…">.
        $accept = [];
        foreach ($allowedTypes as $type => $extensions) {
            $accept[$type] = collect($extensions)
                ->map(fn ($ext) => '.'.ltrim(trim($ext), '.'))
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

        return $formatted.' '.$units[$power];
    }
}
