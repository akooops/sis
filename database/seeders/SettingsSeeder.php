<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Mirrors config('settings.settings') into the settings registry. Adding a
 * setting is: add an entry to the config array, then reseed. No CRUD.
 *
 * Deliberately not updateOrCreate, and that is the whole reason this is not a
 * one-liner: `value` is the only column an admin ever writes, so it is seeded
 * ONLY on the run that creates the row. Everything else — name, type,
 * is_multiple, model_type, options, description, order — is metadata owned by
 * the config file and refreshed every run, so renaming a setting or adding a
 * select option reaches an installed app without resetting what was configured
 * there.
 *
 * Rows whose config entry has gone are left alone rather than swept: a stale row
 * is recoverable (put the entry back, or delete the row), an admin's value is
 * not.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('settings.settings', []) as $group => $settings) {
            foreach ($settings as $key => $setting) {
                $row = Setting::firstOrNew(['group' => $group, 'key' => $key]);

                $multiple = (bool) ($setting['is_multiple'] ?? false);

                $row->fill([
                    'name' => $setting['name'],
                    'type' => $setting['type'],
                    'is_multiple' => $multiple,
                    'model_type' => $setting['model'] ?? null,
                    'description' => $setting['description'] ?? null,
                    'order' => (int) ($setting['sort'] ?? 0),
                ]);

                // Filled separately, and only on a real change — see optionsDiffer().
                // `filter` is compared the same way and for the same reason: MySQL
                // hands JSON objects back in its own key order.
                if ($this->optionsDiffer($row->options, $setting['options'] ?? null)) {
                    $row->options = $setting['options'] ?? null;
                }

                if ($this->optionsDiffer($row->filter, $setting['filter'] ?? null)) {
                    $row->filter = $setting['filter'] ?? null;
                }

                // The guard. A default is an opening position, not a correction:
                // it lands once, with the row. A multiple setting with none falls
                // back to an empty list rather than null, so the form always has
                // something to push onto.
                if (! $row->exists) {
                    $row->value = $setting['default'] ?? ($multiple ? [] : null);
                }

                $row->save();
            }
        }
    }

    /**
     * Whether a select's choices actually moved.
     *
     * MySQL stores json with its object keys in ITS order, not the one they were
     * written in, and Eloquent's dirty check compares the decoded arrays with
     * `===`, which is key-order sensitive. Re-filling an identical options list
     * therefore reads as a change: without this, every reseed UPDATEs each select
     * setting and the observer writes an activity row saying nothing happened.
     *
     * Loose compare on purpose — it ignores key order inside each option while
     * still respecting the order of the options themselves, which is the order
     * the picker renders.
     */
    protected function optionsDiffer(?array $current, ?array $wanted): bool
    {
        // `==` would call null and [] equal, and clearing a list is a real change.
        if ($current === null || $wanted === null) {
            return $current !== $wanted;
        }

        return $current != $wanted;
    }
}
