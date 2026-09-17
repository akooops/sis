<?php

namespace App\Services\Legacy\Importers;

use App\Models\ContactDetail;
use App\Models\Setting;
use App\Services\Legacy\LegacyImporter;

/**
 * The old `settings` table, split three ways.
 *
 * THE OLD TABLE WAS A JUNK DRAWER — twenty-odd rows holding raw markup, API
 * keys, the school's phone numbers, its social links and the site's own
 * headings. This app deliberately keeps SEVEN settings, because anything a
 * visitor reads is a lang key (a settings row holds one value for every locale,
 * so the Arabic site would show the English text) and anything with one right
 * answer is a constant. So most of the old table has no destination, and saying
 * so out loud is this importer's main job.
 *
 * Each legacy row goes to exactly one of three places:
 *
 *  1. A SETTING here, if config('legacy.settings') names one. Only the three
 *     `code.*` markup blocks and the pathway programme qualify.
 *  2. A CONTACT DETAIL row, if config('legacy.contacts') names one. The emails,
 *     phones, address and social links — things this app gives a table to,
 *     because a contact is repeatable, ordered and has a translated label, none
 *     of which a settings value can express.
 *  3. NOWHERE, reported by name. Integration credentials (reCAPTCHA, analytics,
 *     the e-registration API) are the bulk of these: they live on
 *     `integrations.config` here, encrypted, and are pinned to a provider — so
 *     they have to be re-entered by an admin rather than moved by a script that
 *     would have to invent which provider row they belong to.
 */
class SettingsImporter extends LegacyImporter
{
    public function module(): string
    {
        return 'settings';
    }

    public function describe(): string
    {
        return 'Settings → the seven settings, plus contact details';
    }

    public function dependsOn(): array
    {
        return ['programs'];
    }

    public function sources(): array
    {
        return ['settings'];
    }

    public function run(): void
    {
        $rows = [];

        $this->each('settings', function (object $row) use (&$rows) {
            // Decoded ONCE, here, so every reader below sees the real value.
            $row->value = $this->decode($row->value);

            $rows[$row->group.'.'.$row->key] = $row;
        });

        $this->settings($rows);
        $this->contacts($rows);
        $this->unmapped($rows);
    }

    /**
     * Write the handful of legacy rows that have a setting here.
     *
     * The row is UPDATED, never created: settings are seeded from
     * config('settings.php') and carry a type, a name and a picker definition
     * that the old table knows nothing about. A path with no seeded row is a
     * config drift worth reporting, not a row to invent.
     *
     * @param  array<string, object>  $rows
     */
    protected function settings(array $rows): void
    {
        foreach (config('legacy.settings', []) as $legacyPath => $path) {
            $row = $rows[$legacyPath] ?? null;
            $value = $row?->value;

            if ($this->blank($value)) {
                continue;
            }

            [$group, $key] = explode('.', $path, 2);

            $setting = Setting::query()->where('group', $group)->where('key', $key)->first();

            if (! $setting) {
                $this->c->warn("No seeded setting [{$path}] to receive legacy [{$legacyPath}] — run the settings seeder first.");

                continue;
            }

            /*
             * A `model` setting stores a bare id, and the legacy value is a legacy
             * integer id — so it has to go through the import map or it would
             * point at nothing. `pathway_program_id` is the only one today.
             */
            if ($setting->type === 'model') {
                $resolved = $this->c->map->find('programs', $value);

                if (! $resolved) {
                    $this->c->warn("Legacy [{$legacyPath}] points at programme #{$value}, which was not imported — left unset.");

                    continue;
                }

                $value = $resolved;
            }

            $setting->value = $value;

            $this->save($setting, 'settings', (int) $row->id);
        }
    }

    /**
     * The contact half.
     *
     * @param  array<string, object>  $rows
     */
    protected function contacts(array $rows): void
    {
        $order = ContactDetail::query()->max('order');
        $order = $order === null ? 0 : $order + 1;

        foreach (config('legacy.contacts', []) as $legacyPath => $spec) {
            $row = $rows[$legacyPath] ?? null;

            if (! $row || $this->blank($row->value)) {
                continue;
            }

            $values = ($spec['multi'] ?? false)
                ? $this->split($row->value)
                : [trim((string) $row->value)];

            foreach ($values as $index => $value) {
                if ($value === '') {
                    continue;
                }

                $detail = $this->contact($spec, $value, (int) $row->id, $index, $rows);

                if ($detail && ! $detail->exists) {
                    $detail->order = $order++;
                }

                if ($detail) {
                    // The legacy id is multiplied by 100 and offset so several
                    // contacts born of ONE settings row still get one map entry
                    // each — without it, a re-run would reconcile all of them
                    // onto the first and drop the rest.
                    $this->save($detail, 'settings_contacts', (int) $row->id * 100 + $index);
                }
            }
        }
    }

    /**
     * One contact row.
     *
     * @param  array<string, mixed>  $spec
     * @param  array<string, object>  $rows
     */
    protected function contact(array $spec, string $value, int $legacyId, int $index, array $rows): ?ContactDetail
    {
        $type = $spec['type'];

        $detail = $this->model(ContactDetail::class, 'settings_contacts', $legacyId * 100 + $index);

        $detail->type = $type;
        $detail->name = $spec['name'].($index > 0 ? ' '.($index + 1) : '');

        if ($type === ContactDetail::ADDRESS_TYPE) {
            /*
             * An address has no scalar value at all here — its text is translated
             * in the `address` column. The old setting held one untranslated
             * string, so it seeds the DEFAULT locale only and the rest are left
             * for an admin: a single string copied into nine locales would make
             * untranslated text look translated, which is the same rule
             * FormsSeeder::translate() enforces.
             */
            $detail->value = null;
            $detail->setTranslation('address', config('app.locale'), $value);
            $detail->map_url = $rows[config('legacy.map_setting')]->value ?? $detail->map_url;

            return $detail;
        }

        if ($type === ContactDetail::SOCIAL_TYPE) {
            $detail->platform = $spec['platform'] ?? null;
        }

        $detail->value = $type === 'phone' || $type === 'whatsapp'
            ? ($this->phone($value) ?? $value)
            : $value;

        return $detail;
    }

    /**
     * Whether a decoded setting holds nothing.
     *
     * Its own helper because decode() can hand back an array, and the old table
     * is full of rows written as the JSON string `""` — two characters, which a
     * raw emptiness test reads as a value and then reports as an unmapped
     * setting that was never set.
     */
    protected function blank(mixed $value): bool
    {
        if (is_array($value)) {
            return $this->split($value) === [];
        }

        return trim((string) $value) === '';
    }

    /**
     * UNWRAP THE OLD ADMIN'S JSON ENCODING, AND IT IS NOT ALWAYS ONE LAYER.
     *
     * The legacy settings form json_encoded whatever it was given before writing
     * it, so `head_code` is stored as `"<!-- Google tag …"` — quotes included —
     * and every URL is stored with its slashes escaped (`https:\/\/…`). Read raw,
     * the Custom Head Code block would arrive wrapped in a stray quote pair and
     * every social link would be a backslashed string that does not resolve.
     *
     * The multi-value rows are encoded TWICE: `phones` holds the string
     * `["(966) 920002877"]`, itself json_encoded — an array that was serialised
     * on the way in and then serialised again on the way out. So this peels
     * repeatedly, stopping as soon as it reaches an array or something that is no
     * longer JSON.
     *
     * THE FIRST-CHARACTER GATE IS WHAT KEEPS IT SAFE. json_decode('4') is a valid
     * int, so an unguarded loop would turn the string `"4"` — a programme id —
     * into the integer 4 and then into something the map cannot look up. Only a
     * value that actually opens with `"`, `[` or `{` is treated as encoded.
     */
    protected function decode(?string $value): mixed
    {
        $value = (string) $value;

        // Three is one more than the deepest nesting seen in real data, so a
        // fourth layer means something is wrong and is left alone rather than
        // unwrapped until it stops looking like JSON.
        for ($i = 0; $i < 3; $i++) {
            $trimmed = trim($value);
            $first = $trimmed[0] ?? '';

            if (! in_array($first, ['"', '[', '{'], true)) {
                break;
            }

            $decoded = json_decode($trimmed, true);

            if ($decoded === null) {
                break;
            }

            // An array is the answer; a string may still be wrapping one.
            if (is_array($decoded)) {
                return $decoded;
            }

            if (! is_string($decoded)) {
                break;
            }

            $value = $decoded;
        }

        return $value;
    }

    /**
     * One legacy value holding several entries.
     *
     * decode() has already turned a JSON array into a real one. What is left is
     * the textarea case: the old admin also let someone type several numbers
     * separated by newlines or commas, so both are accepted rather than assuming
     * whichever shape this install happened to use.
     *
     * @return array<int, string>
     */
    protected function split(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                fn ($item) => is_scalar($item) ? trim((string) $item) : '',
                $value,
            )));
        }

        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[\r\n,;]+/', $value) ?: [],
        )));
    }

    /**
     * Say what was left behind, by name.
     *
     * @param  array<string, object>  $rows
     */
    protected function unmapped(array $rows): void
    {
        $handled = array_merge(
            array_keys(config('legacy.settings', [])),
            array_keys(config('legacy.contacts', [])),
            [config('legacy.map_setting')],
        );

        $left = array_values(array_filter(
            array_keys($rows),
            fn (string $path) => ! in_array($path, $handled, true) && ! $this->blank($rows[$path]->value),
        ));

        if ($left === []) {
            return;
        }

        $this->c->note(
            count($left).' legacy setting(s) have no destination in this app and were not carried across: '
            .implode(', ', $left).'. Credentials belong on an Integration here (encrypted, pinned to a provider); '
            .'anything a visitor reads is a lang key in the `common` group.'
        );
    }
}
