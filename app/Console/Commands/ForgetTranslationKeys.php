<?php

namespace App\Console\Commands;

use App\Models\Language;
use App\Models\TranslationKey;
use App\Services\Translations\TranslationService;
use Illuminate\Console\Command;

/**
 * Retire translation keys: the lang lines in every locale AND the registry rows.
 *
 * THE COUNTERPART THE CATALOGUE WAS MISSING. Adding a key is "add it to
 * TranslationKeysSeeder::catalogue(), reseed". Removing one had no path at all —
 * dropping the catalogue entry removes nothing from an install that has already
 * seeded, because putMany() only ever writes. So a retired key kept resolving
 * through __() forever on every existing install, while a fresh one no longer
 * had it: two installs of the same commit disagreeing about what a key means.
 *
 * BOTH HALVES OR NEITHER, which is the whole reason this is a command rather
 * than two things to remember. `lang/` is generated and gitignored, so a deploy
 * carries the catalogue edit but not the files; and a registry row left behind
 * is worse than a stale line. With config('translations.fill_missing_keys')
 * false nothing notices today — but flip it true and the next admin edit to ANY
 * key in that group re-inserts the removed one as '' in every locale, and ''
 * is present, so Laravel's fallback never fires and every locale renders a blank
 * where English used to be.
 *
 * REMOVING A KEY THAT IS STILL RENDERED PRINTS THE RAW DOTTED STRING to a
 * visitor, so --dry-run is the default posture: run it, read what it would take,
 * then run it for real.
 */
class ForgetTranslationKeys extends Command
{
    protected $signature = 'translations:forget
        {group : The catalogue group, e.g. site}
        {keys* : One or more dotted keys, e.g. newsletters.subscribe.consent}
        {--dry-run : Report what would be removed and change nothing}';

    protected $description = 'Remove translation keys from every locale file and from the key registry';

    public function handle(TranslationService $service): int
    {
        $group = (string) $this->argument('group');
        $keys = (array) $this->argument('keys');
        $dry = (bool) $this->option('dry-run');

        if (! in_array($group, (array) config('translations.groups', []), true)) {
            $this->error("\"{$group}\" is not a registered group. See config('translations.groups').");

            return self::FAILURE;
        }

        /*
         * A key nobody registered is either already gone or a typo, and a typo
         * here silently does nothing — so say which is which before touching a
         * file, and let the operator decide.
         */
        $registered = TranslationKey::query()
            ->where('group', $group)
            ->whereIn('key', $keys)
            ->pluck('key')
            ->all();

        foreach (array_diff($keys, $registered) as $unregistered) {
            $this->warn("{$group}.{$unregistered} is not in the registry — its lang lines will still be removed if present.");
        }

        if ($dry) {
            $this->line('Dry run. Nothing was changed.');
        }

        $total = 0;

        foreach (Language::query()->pluck('code') as $code) {
            $removed = $dry
                ? count(array_filter($keys, fn (string $key) => $service->isTranslated($code, $group, $key)))
                : $service->remove($code, $group, $keys);

            $total += $removed;

            $this->line(sprintf('  %-4s %d line(s)%s', $code, $removed, $dry ? ' would be removed' : ' removed'));
        }

        if (! $dry) {
            $rows = TranslationKey::query()->where('group', $group)->whereIn('key', $keys)->delete();

            $this->line("  registry: {$rows} row(s) deleted");
        }

        $this->info($dry
            ? "Would remove {$total} line(s) across ".count($keys).' key(s).'
            : "Removed {$total} line(s) across ".count($keys).' key(s).');

        // Deliberately last and unconditional: the files and the registry are
        // only two of the three places a key lives, and the third is source.
        $this->comment('Remember to drop these from TranslationKeysSeeder::catalogue(), or the next reseed puts them back.');

        return self::SUCCESS;
    }
}
