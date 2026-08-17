<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormPage;
use App\Models\Language;
use App\States\Form\Published;
use Illuminate\Database\Seeder;
use RuntimeException;

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

    protected ?string $defaultCode = null;

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
    protected function translate(array|string|null $lines, ?string $fallback = null): array
    {
        // A bare string is one untranslated line. Accepted so a definition can
        // stay terse where a value genuinely has no translation — but it lands
        // in the default locale only, and the audit will say so.
        if (is_string($lines)) {
            $lines = [Language::defaultCode() => $lines];
        }

        $out = [];

        foreach ($this->codes as $code) {
            if (! empty($lines[$code])) {
                $out[$code] = $lines[$code];
            }
        }

        if ($out !== []) {
            return $out;
        }

        // No fallback offered means the element genuinely has nothing to say
        // here — a hidden field has no label, a text input has no content — and
        // inventing one from the machine key would put "job_offer_id" in a
        // translatable column for every locale to carry around.
        return $fallback === null || $fallback === ''
            ? []
            : [Language::defaultCode() => $fallback];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function buildStructure(Form $form, array $definition): void
    {
        $this->assertKeysUnique($definition);

        foreach ($definition['pages'] ?? [['name' => 'Page 1', 'fields' => $definition['fields'] ?? []]] as $pageIndex => $pageDefinition) {
            $page = FormPage::create([
                'form_id' => $form->id,
                'name' => $pageDefinition['name'] ?? 'Page '.($pageIndex + 1),
                'order' => $pageIndex,
                'is_interstitial' => (bool) ($pageDefinition['is_interstitial'] ?? false),
                'title' => $this->translate($pageDefinition['title'] ?? null),
            ]);

            foreach ($pageDefinition['fields'] ?? [] as $fieldIndex => $fieldDefinition) {
                $field = $this->buildField($form, $page, $fieldDefinition, $fieldIndex, null);

                /*
                 * A repeatable group's children, one level down. `order` restarts
                 * at zero because a child's position is within its GROUP, not on
                 * the page — the same rule FormField::nextOrder() applies.
                 */
                foreach ($fieldDefinition['children'] ?? [] as $childIndex => $childDefinition) {
                    $this->buildField($form, $page, $childDefinition, $childIndex, $field->id);
                }
            }
        }
    }

    /**
     * FIELD KEYS ARE UNIQUE PER FORM, INCLUDING A GROUP'S CHILDREN.
     *
     * Checked here because the database enforces it as `unique(form_id, key)` and
     * a clash surfaces as a bare PDO "Duplicate entry" from inside a half-built
     * form — the seeder stops mid-structure, and because run() only builds when
     * the form was newly created, re-running does not repair it. The definition
     * has to be deleted and reseeded, so the error is worth catching before the
     * first row is written.
     *
     * Two groups therefore cannot both name a child `start_year`; the second must
     * pick its own key and config/jobs.php maps it back to the column. That is not
     * a wart — form-wide uniqueness is load-bearing, because the upload endpoint
     * resolves a file field by key ALONE and could not otherwise tell two groups'
     * `certificate` fields apart.
     *
     * @param  array<string, mixed>  $definition
     */
    protected function assertKeysUnique(array $definition): void
    {
        $seen = [];

        foreach ($definition['pages'] ?? [] as $page) {
            foreach ($page['fields'] ?? [] as $field) {
                foreach ([$field, ...($field['children'] ?? [])] as $element) {
                    $key = $element['key'] ?? null;

                    if ($key === null) {
                        continue;
                    }

                    if (isset($seen[$key])) {
                        throw new RuntimeException(
                            "Form [{$definition['slug']}] declares the field key \"{$key}\" more than once. "
                            .'Keys are unique per form, group children included.'
                        );
                    }

                    $seen[$key] = true;
                }
            }
        }
    }

    /**
     * One element, on the page or inside a group.
     *
     * @param  array<string, mixed>  $definition
     */
    protected function buildField(Form $form, FormPage $page, array $definition, int $order, ?string $parentId): FormField
    {
        $field = FormField::create([
            'form_id' => $form->id,
            'form_page_id' => $page->id,
            'parent_form_field_id' => $parentId,
            'type' => $definition['type'],
            'key' => $definition['key'],
            'order' => $order,
            'is_required' => (bool) ($definition['is_required'] ?? false),
            'settings' => $definition['settings'] ?? null,
            'validation' => $definition['validation'] ?? null,
            // No key fallback: an element without a label has none to show, and
            // seeding the machine key as one puts `job_offer_id` in front of a
            // visitor the moment somebody flips it visible.
            'label' => $this->translate($definition['label'] ?? null),
            'placeholder' => $this->translate($definition['placeholder'] ?? null),
            'content' => $this->translate($definition['content'] ?? null),
            'value' => $this->translate($definition['value'] ?? null),
        ]);

        foreach ($this->optionsFor($definition) as $optionIndex => $option) {
            FormFieldOption::create([
                'form_field_id' => $field->id,
                'value' => $option['value'],
                'order' => $optionIndex,
                // Option VALUES are never translated — the same answer has to
                // read identically whatever language it was given in.
                'label' => $this->optionLabel($option),
            ]);
        }

        return $field;
    }

    /**
     * An option's label, translated or language-neutral.
     *
     * A MAP is prose and narrows like everything else — a nationality option
     * carries the country's translated demonym, so it takes this branch. A BARE
     * STRING, or no label at all, means the label IS a code — the academic year
     * `2026/2027`, or the English country name standing in for a row with no
     * demonym recorded — and reads the same in every language, so it is written
     * to every locale rather than to the default one.
     *
     * That distinction is why translate() stays strict elsewhere: a bare string
     * where a map belongs is untranslated copy, and filling it across nine
     * locales would make the audit call it translated. Only here is a bare
     * string a deliberate statement about the value.
     *
     * @param  array<string, mixed>  $option
     * @return array<string, string>
     */
    protected function optionLabel(array $option): array
    {
        $label = $option['label'] ?? null;

        if (is_array($label)) {
            return $this->translate($label);
        }

        $text = (string) ($label ?? $option['value']);

        return $text === '' ? [] : array_fill_keys($this->codes, $text);
    }

    /**
     * The options a field declares, or a list generated from a table.
     *
     * `options_from` exists for exactly one case: nationality. Writing 250
     * countries into config/forms.php in nine languages would treble the file to
     * restate a table the app already has — and the table carries the
     * TRANSLATIONS as well as the codes, so the copy would be nine columns wide
     * and stale the moment a demonym was corrected in any one of them.
     * Everything else lists its options inline, where they are readable.
     *
     * @param  array<string, mixed>  $definition
     * @return array<int, array<string, mixed>>
     */
    protected function optionsFor(array $definition): array
    {
        if (($definition['options_from'] ?? null) !== 'countries') {
            return array_values($definition['options'] ?? []);
        }

        // `title` and `nationality` are translatable casts, so they have to be
        // named in the projection — a ['code', 'name'] select reads them null.
        $countries = Country::query()->get(['code', 'name', 'title', 'nationality']);

        /*
         * DEMONYMS ARE NOT UNIQUE ACROSS ISO ROWS, and the collisions are exactly
         * the ones that matter: three rows are "Norwegian" (Norway, Bouvet Island,
         * Svalbard), three are "British" (the UK and two territories), and so on
         * for Australian, American, French, Congolese and Dominican — 16 rows
         * across 7 labels. Left alone, an applicant picks one of three identical
         * "Norwegian" entries and two of them store an uninhabited territory,
         * which then feeds a scorer that reasons explicitly about work
         * authorisation by country. Nothing about that failure is visible: the
         * form validates, the submission completes, the row looks plausible.
         *
         * So a demonym is qualified with the country's own name ONLY where it is
         * ambiguous. 233 options stay clean single words; the 16 that could not
         * be told apart say which country they mean, in every locale.
         */
        $ambiguous = $countries
            ->countBy(fn (Country $country) => $country->getTranslation('nationality', $this->default()) ?: $country->code)
            ->filter(fn (int $count) => $count > 1);

        $options = $countries
            ->map(fn (Country $country) => [
                // The ISO code is the VALUE, so an answer means the same thing
                // whatever language it was given in — and ApplicationProjector
                // resolves a country by code as well as by id.
                'value' => $country->code,
                'label' => $this->countryLabel(
                    $country,
                    $ambiguous->has($country->getTranslation('nationality', $this->default()) ?: $country->code),
                ),
            ]);

        /*
         * SORTED BY WHAT THE VISITOR ACTUALLY READS, in the default locale.
         *
         * This used to order by the English `name` column on the grounds that it
         * is one canonical order. That was right while the label WAS the name;
         * now the label is the demonym, so ordering by `name` sequenced 249
         * entries by a string nobody sees — "Motswana" between "Bosnian" and
         * "Brazilian", because Botswana sorts there. It is a required select with
         * no search, and a native select's type-ahead matches the VISIBLE text,
         * so an unsorted list is the difference between typing "n" and scrolling
         * 249 rows.
         *
         * One `order` column serves all nine locales, so only one of them can be
         * alphabetical — the default is the one to pick. Enabling a locale does
         * not renumber anything, because it does not change the default; only
         * changing the default locale does, and that is a deliberate act.
         */
        return $options
            ->sortBy(
                fn (array $option) => is_array($option['label'])
                    ? ($option['label'][$this->default()] ?? '')
                    : $option['label'],
                SORT_NATURAL | SORT_FLAG_CASE,
            )
            ->values()
            ->all();
    }

    /** The install's default locale, read once per seeder run. */
    protected function default(): string
    {
        return $this->defaultCode ??= Language::defaultCode();
    }

    /**
     * What an applicant reads in the nationality select: the DEMONYM, translated.
     *
     * The map goes on to optionLabel() and through translate() like any other
     * prose, so it narrows to the locales this install has — which is the whole
     * point: an Arabic applicant picks "سعودي", not "Saudi Arabia".
     *
     * The country's own translated name stands in when no demonym is recorded,
     * and the plain English `name` when neither is. A country with an incomplete
     * row must still be PICKABLE — a blank option is one nobody can choose their
     * nationality from, and being unable to apply at all is a far worse outcome
     * than reading an untranslated label.
     *
     * Each step is decided on the STORED map, not on what this install would
     * render from it, so the choice does not depend on the seeder's locale list
     * having been loaded first. A map covering only some locales is not a blank
     * option either way: the renderer falls back to the default locale
     * (site/js/lib/forms/i18n.js), so a gap reads English rather than empty.
     *
     * $qualify appends the country's own name — "Norwegian (Svalbard and Jan
     * Mayen)" — and is set only for the demonyms that collide. Per locale, so an
     * Arabic reader gets the Arabic country name in the brackets too; where a
     * locale is missing one half, that locale simply keeps the half it has
     * rather than printing an empty pair of brackets.
     *
     * @return array<string, string>|string
     */
    protected function countryLabel(Country $country, bool $qualify = false): array|string
    {
        foreach (['nationality', 'title'] as $attribute) {
            $lines = array_filter($country->getTranslations($attribute), fn ($line) => filled($line));

            if ($lines === []) {
                continue;
            }

            // Only the demonym is ever ambiguous; `title` IS the country name,
            // so qualifying it with itself would read "Norway (Norway)".
            if (! $qualify || $attribute !== 'nationality') {
                return $lines;
            }

            $names = $country->getTranslations('title');

            return collect($lines)
                ->map(fn (string $demonym, string $locale) => filled($names[$locale] ?? null)
                    ? $demonym.' ('.$names[$locale].')'
                    : $demonym)
                ->all();
        }

        return $country->name;
    }
}
