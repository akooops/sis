<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormPage;
use App\Models\Language;
use App\States\Form\Published;
use Illuminate\Database\Seeder;

/**
 * Forms the app itself resolves by slug. `is_system` freezes the slug and blocks
 * the delete, and UpdateBuilderData::withValidator() (via Form::isLocked()) blocks
 * changes to the pages and fields — the settings stay editable.
 *
 * TRANSLATED ACROSS EVERY SEEDED LOCALE, AND THE WORDING IS IN ONE FILE. The
 * definitions in config('forms.system') carry `title`, `confirmation_message` and
 * `label` as locale => string maps, and this narrows each to the locales that
 * actually have a Language row. So a fresh install has a contact form that reads
 * correctly in Arabic, not an English one with eight empty locales.
 *
 * This used to resolve catalogue KEYS against the `forms` group of
 * config/translations.php, which put a seeded form's wording in a second file and
 * made the catalogue carry eighteen keys nothing resolved at runtime.
 *
 * firstOrCreate for the form, matching PagesSeeder: a form row is authored
 * content and a reseed must never clobber copy an admin has since written.
 * Structure is only created when the form itself is new, for the same reason —
 * re-running this must not resurrect a page someone deliberately removed, nor
 * duplicate one.
 *
 * Runs AFTER LanguagesSeeder, whose rows decide which locales are written.
 */
class FormsSeeder extends Seeder
{
    /** @var array<int, string> */
    protected array $codes = [];

    public function run(): void
    {
        $forms = config('forms.system', []);

        if ($forms === []) {
            return;
        }

        $this->codes = Language::query()->pluck('code')->all();

        foreach ($forms as $definition) {
            $form = Form::firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'title' => $this->translate($definition['title'] ?? null, $definition['name']),
                    'confirmation_message' => $this->translate(
                        $definition['confirmation_message'] ?? null,
                        'Thank you. Your response has been recorded.',
                    ),
                    'status' => Published::class,
                    'published_at' => now(),
                    'is_system' => true,
                ],
            );

            // An existing form adopted into the system set: assert the lock only.
            if (! $form->wasRecentlyCreated) {
                if (! $form->is_system) {
                    $form->forceFill(['is_system' => true])->save();
                }

                continue;
            }

            $this->buildStructure($form, $definition);
        }
    }

    /**
     * A config locale => string map, narrowed to the locales this install has.
     *
     * config/forms.php carries all nine; a Language row is what decides which of
     * them are written, so an install running three languages seeds three and not
     * nine columns of copy nobody can read.
     *
     * A missing or empty map falls back to the supplied English string in the
     * default locale, so a typo degrades to one untranslated label rather than a
     * blank form.
     *
     * @param  array<string, string>|null  $lines
     * @return array<string, string>
     */
    protected function translate(?array $lines, string $fallback): array
    {
        $out = [];

        foreach ($this->codes as $code) {
            if (! empty($lines[$code])) {
                $out[$code] = $lines[$code];
            }
        }

        return $out ?: [Language::defaultCode() => $fallback];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function buildStructure(Form $form, array $definition): void
    {
        $default = Language::defaultCode();

        foreach ($definition['pages'] ?? [['name' => 'Page 1', 'fields' => $definition['fields'] ?? []]] as $pageIndex => $pageDefinition) {
            $page = FormPage::create([
                'form_id' => $form->id,
                'name' => $pageDefinition['name'] ?? 'Page '.($pageIndex + 1),
                'order' => $pageIndex,
                'is_interstitial' => (bool) ($pageDefinition['is_interstitial'] ?? false),
                'title' => [$default => $pageDefinition['title'] ?? ''],
            ]);

            foreach ($pageDefinition['fields'] ?? [] as $fieldIndex => $fieldDefinition) {
                $field = FormField::create([
                    'form_id' => $form->id,
                    'form_page_id' => $page->id,
                    'type' => $fieldDefinition['type'],
                    'key' => $fieldDefinition['key'],
                    'order' => $fieldIndex,
                    'is_required' => (bool) ($fieldDefinition['is_required'] ?? false),
                    'settings' => $fieldDefinition['settings'] ?? null,
                    'validation' => $fieldDefinition['validation'] ?? null,
                    'label' => $this->translate(
                        $fieldDefinition['label'] ?? null,
                        $fieldDefinition['key'],
                    ),
                    'content' => [$default => $fieldDefinition['content'] ?? ''],
                ]);

                foreach ($fieldDefinition['options'] ?? [] as $optionIndex => $option) {
                    FormFieldOption::create([
                        'form_field_id' => $field->id,
                        'value' => $option['value'],
                        'order' => $optionIndex,
                        // Option VALUES are never translated — the same answer has
                        // to read identically whatever language it was given in.
                        // These labels are codes (2026/2027, Grade 4), so they are
                        // seeded once and left for an admin to localise if wanted.
                        'label' => [$default => $option['label'] ?? $option['value']],
                    ]);
                }
            }
        }
    }
}
