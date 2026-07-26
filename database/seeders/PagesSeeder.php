<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Page;
use App\States\Page\Published;
use Illuminate\Database\Seeder;

/**
 * Pages the app itself resolves by slug. `is_system` freezes the slug and blocks
 * the delete; everything else about the row stays editable.
 *
 * The array is intentionally empty: the capability ships, the content doesn't.
 * Add a row when you know a slug the code actually depends on.
 *
 * firstOrCreate, NOT updateOrCreate — a deliberate divergence from
 * LanguagesSeeder. A language row is pure metadata, so overwriting it on reseed
 * is harmless; a page row is authored content, and a reseed must never clobber
 * copy an admin has since written. Only the lock is re-asserted.
 */
class PagesSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<int, array{slug: string, name: string, title: string}> $pages */
        $pages = [
            // ['slug' => 'home', 'name' => 'Home', 'title' => 'Home'],
        ];

        if ($pages === []) {
            return;
        }

        $default = Language::defaultCode();

        foreach ($pages as $definition) {
            $page = Page::firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'title' => [$default => $definition['title']],
                    'status' => Published::class,
                    'published_at' => now(),
                    'is_system' => true,
                ],
            );

            // An existing page adopted into the system set: assert the lock only.
            if (! $page->wasRecentlyCreated && ! $page->is_system) {
                $page->forceFill(['is_system' => true])->save();
            }
        }
    }
}
