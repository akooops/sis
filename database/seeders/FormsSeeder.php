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
 * TRANSLATED ACROSS EVERY SEEDED LOCALE, AND THE WORDING IS IN THIS FILE. The
 * definitions in forms() carry `title`, `confirmation_message` and `label` as
 * locale => string maps, and this narrows each to the locales that actually have
 * a Language row. So a fresh install has a contact form that reads correctly in
 * Arabic, not an English one with eight empty locales.
 *
 * THE DEFINITIONS LIVE HERE, NOT IN CONFIG. They are seed content — a thousand
 * lines read once, by this class, to write rows an admin then owns — and config/
 * is for settings the running app reads. What stays in config/forms.php is the
 * settings half: the field-type registry, the projector and rule maps, spam,
 * upload, webhook, limit, geo and capture knobs, all of which ARE read per
 * request. Leaving the definitions there made `config:cache` serialise nine
 * locales of form copy into every request's bootstrap.
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
        $forms = $this->forms();

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

    /**
     * The 15 grade options, prek through g12, in 9 locales.
     *
     * SHARED BY EVERY FORM THAT ASKS WHAT YEAR A CHILD IS IN — the admissions
     * inquiry and the visit reservation today. It is a method rather than 165
     * lines repeated per form because the two lists have to agree: an inquiry
     * and a booking for the same child should store the same value, and a school
     * that renames a year should not have to find every form that spells it.
     *
     * A FIXED LIST rather than one generated from the grades table. The visitor
     * is telling us what year their child is in AT THEIR CURRENT SCHOOL, which is
     * not necessarily a year this school offers, and binding the question to our
     * own catalogue would make an applicant from outside the system unable to
     * answer it truthfully.
     *
     * Option VALUES are never translated — the same answer has to read identically
     * whatever language it was given in, which is what makes an export comparable.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function gradeOptions(): array
    {
        return [
            ['value' => 'prek', 'label' => [
                'en' => 'Pre-K',
                'ar' => 'ما قبل الروضة',
                'fr' => 'Petite section',
                'es' => 'Preescolar',
                'de' => 'Vorschule',
                'it' => 'Prescolare',
                'pt' => 'Pré-escolar',
                'ru' => 'Дошкольная группа',
                'hi' => 'प्री-के',
            ]],
            ['value' => 'kg1', 'label' => [
                'en' => 'KG1',
                'ar' => 'الروضة الأولى',
                'fr' => 'Maternelle 1',
                'es' => 'Infantil 1',
                'de' => 'Kindergarten 1',
                'it' => 'Materna 1',
                'pt' => 'Jardim 1',
                'ru' => 'Подготовка 1',
                'hi' => 'केजी1',
            ]],
            ['value' => 'kg2', 'label' => [
                'en' => 'KG2',
                'ar' => 'الروضة الثانية',
                'fr' => 'Maternelle 2',
                'es' => 'Infantil 2',
                'de' => 'Kindergarten 2',
                'it' => 'Materna 2',
                'pt' => 'Jardim 2',
                'ru' => 'Подготовка 2',
                'hi' => 'केजी2',
            ]],
            ['value' => 'g1', 'label' => [
                'en' => 'Grade 1',
                'ar' => 'الصف 1',
                'fr' => 'Année 1',
                'es' => 'Curso 1',
                'de' => 'Klasse 1',
                'it' => 'Classe 1',
                'pt' => 'Ano 1',
                'ru' => '1-й класс',
                'hi' => 'कक्षा 1',
            ]],
            ['value' => 'g2', 'label' => [
                'en' => 'Grade 2',
                'ar' => 'الصف 2',
                'fr' => 'Année 2',
                'es' => 'Curso 2',
                'de' => 'Klasse 2',
                'it' => 'Classe 2',
                'pt' => 'Ano 2',
                'ru' => '2-й класс',
                'hi' => 'कक्षा 2',
            ]],
            ['value' => 'g3', 'label' => [
                'en' => 'Grade 3',
                'ar' => 'الصف 3',
                'fr' => 'Année 3',
                'es' => 'Curso 3',
                'de' => 'Klasse 3',
                'it' => 'Classe 3',
                'pt' => 'Ano 3',
                'ru' => '3-й класс',
                'hi' => 'कक्षा 3',
            ]],
            ['value' => 'g4', 'label' => [
                'en' => 'Grade 4',
                'ar' => 'الصف 4',
                'fr' => 'Année 4',
                'es' => 'Curso 4',
                'de' => 'Klasse 4',
                'it' => 'Classe 4',
                'pt' => 'Ano 4',
                'ru' => '4-й класс',
                'hi' => 'कक्षा 4',
            ]],
            ['value' => 'g5', 'label' => [
                'en' => 'Grade 5',
                'ar' => 'الصف 5',
                'fr' => 'Année 5',
                'es' => 'Curso 5',
                'de' => 'Klasse 5',
                'it' => 'Classe 5',
                'pt' => 'Ano 5',
                'ru' => '5-й класс',
                'hi' => 'कक्षा 5',
            ]],
            ['value' => 'g6', 'label' => [
                'en' => 'Grade 6',
                'ar' => 'الصف 6',
                'fr' => 'Année 6',
                'es' => 'Curso 6',
                'de' => 'Klasse 6',
                'it' => 'Classe 6',
                'pt' => 'Ano 6',
                'ru' => '6-й класс',
                'hi' => 'कक्षा 6',
            ]],
            ['value' => 'g7', 'label' => [
                'en' => 'Grade 7',
                'ar' => 'الصف 7',
                'fr' => 'Année 7',
                'es' => 'Curso 7',
                'de' => 'Klasse 7',
                'it' => 'Classe 7',
                'pt' => 'Ano 7',
                'ru' => '7-й класс',
                'hi' => 'कक्षा 7',
            ]],
            ['value' => 'g8', 'label' => [
                'en' => 'Grade 8',
                'ar' => 'الصف 8',
                'fr' => 'Année 8',
                'es' => 'Curso 8',
                'de' => 'Klasse 8',
                'it' => 'Classe 8',
                'pt' => 'Ano 8',
                'ru' => '8-й класс',
                'hi' => 'कक्षा 8',
            ]],
            ['value' => 'g9', 'label' => [
                'en' => 'Grade 9',
                'ar' => 'الصف 9',
                'fr' => 'Année 9',
                'es' => 'Curso 9',
                'de' => 'Klasse 9',
                'it' => 'Classe 9',
                'pt' => 'Ano 9',
                'ru' => '9-й класс',
                'hi' => 'कक्षा 9',
            ]],
            ['value' => 'g10', 'label' => [
                'en' => 'Grade 10',
                'ar' => 'الصف 10',
                'fr' => 'Année 10',
                'es' => 'Curso 10',
                'de' => 'Klasse 10',
                'it' => 'Classe 10',
                'pt' => 'Ano 10',
                'ru' => '10-й класс',
                'hi' => 'कक्षा 10',
            ]],
            ['value' => 'g11', 'label' => [
                'en' => 'Grade 11',
                'ar' => 'الصف 11',
                'fr' => 'Année 11',
                'es' => 'Curso 11',
                'de' => 'Klasse 11',
                'it' => 'Classe 11',
                'pt' => 'Ano 11',
                'ru' => '11-й класс',
                'hi' => 'कक्षा 11',
            ]],
            ['value' => 'g12', 'label' => [
                'en' => 'Grade 12',
                'ar' => 'الصف 12',
                'fr' => 'Année 12',
                'es' => 'Curso 12',
                'de' => 'Klasse 12',
                'it' => 'Classe 12',
                'pt' => 'Ano 12',
                'ru' => '12-й класс',
                'hi' => 'कक्षा 12',
            ]],
        ];
    }

    /**
     * Forms that ship with the app. run() creates these as is_system, so their
     * settings stay editable but their structure does not.
     *
     * The first two exist because the public site RESOLVES THEM BY SLUG:
     * /{locale}/contact and /{locale}/inquiries each look up one of these and
     * render it with the ordinary form renderer, so renaming a slug would
     * 404 a page. is_system is what makes that safe — UpdateFormData freezes the
     * slug with Rule::in([$form->slug]), Form::isLocked() blocks structural
     * edits in the builder, and FormsController::destroy refuses the delete.
     * Copy, wording, notification routing and webhooks all stay editable.
     *
     * WORDING IS INLINE, NOT A CATALOGUE KEY. Every title, confirmation and
     * label below is a locale => string map, so this method alone says what the
     * seeded forms read like in all nine languages, and translate() needs
     * nothing but what it can see.
     *
     * It used to hold `title_key` / `confirmation_key` / `label_key` pointing
     * into the `forms` group of config/translations.php, which meant reading a
     * seeded form took two files and a lookup, and the catalogue carried
     * eighteen keys nothing ever resolved at runtime — they existed purely to
     * be copied into the database once. The lang files keep the strings the
     * app itself resolves (forms.submit, forms.closed, …); a seed value is not
     * one of those.
     *
     * THE SEED IS A STARTING POINT, NOT A BINDING. These land in translatable
     * columns on the row, and an admin edits them afterwards in the builder —
     * changing a string here never rewrites a form that already exists.
     *
     * Settings and validation keys below are the ones the FieldType classes
     * actually declare — `email` and `phone` declare NONE, so nothing is passed
     * to them. Check app/Services/Forms/FieldTypes/*.php before adding a key;
     * an unknown one is silently ignored rather than rejected.
     *
     * @return array<int, array<string, mixed>>
     */
    private function forms(): array
    {
        return [
            [
                'slug' => 'contact',
                'name' => 'Contact',
                'title' => [
                    'en' => 'Contact us',
                    'ar' => 'اتصل بنا',
                    'fr' => 'Nous contacter',
                    'es' => 'Contacto',
                    'de' => 'Kontakt',
                    'it' => 'Contattaci',
                    'pt' => 'Contacte-nos',
                    'ru' => 'Свяжитесь с нами',
                    'hi' => 'हमसे संपर्क करें',
                ],
                'confirmation_message' => [
                    'en' => 'Thank you for contacting us. We will reply shortly.',
                    'ar' => 'شكرًا لتواصلك معنا. سنرد عليك قريبًا.',
                    'fr' => 'Merci de nous avoir contactés. Nous vous répondrons sous peu.',
                    'es' => 'Gracias por ponerse en contacto. Le responderemos en breve.',
                    'de' => 'Danke für Ihre Nachricht. Wir melden uns in Kürze.',
                    'it' => 'Grazie per averci contattato. Ti risponderemo a breve.',
                    'pt' => 'Obrigado por nos contactar. Responderemos em breve.',
                    'ru' => 'Спасибо за обращение. Мы скоро ответим.',
                    'hi' => 'हमसे संपर्क करने के लिए धन्यवाद। हम शीघ्र ही उत्तर देंगे।',
                ],
                'pages' => [
                    [
                        'name' => 'Contact',
                        'fields' => [
                            [
                                'type' => 'text',
                                'key' => 'name',
                                'is_required' => true,
                                'validation' => ['max_length' => 120],
                                'label' => [
                                    'en' => 'Full name',
                                    'ar' => 'الاسم الكامل',
                                    'fr' => 'Nom complet',
                                    'es' => 'Nombre completo',
                                    'de' => 'Vollständiger Name',
                                    'it' => 'Nome completo',
                                    'pt' => 'Nome completo',
                                    'ru' => 'Полное имя',
                                    'hi' => 'पूरा नाम',
                                ],
                            ],
                            [
                                'type' => 'email',
                                'key' => 'email',
                                'is_required' => true,
                                'label' => [
                                    'en' => 'Email address',
                                    'ar' => 'البريد الإلكتروني',
                                    'fr' => 'Adresse e-mail',
                                    'es' => 'Correo electrónico',
                                    'de' => 'E-Mail-Adresse',
                                    'it' => 'Indirizzo e-mail',
                                    'pt' => 'Endereço de e-mail',
                                    'ru' => 'Электронная почта',
                                    'hi' => 'ईमेल पता',
                                ],
                            ],
                            [
                                'type' => 'phone',
                                'key' => 'phone',
                                'label' => [
                                    'en' => 'Phone number',
                                    'ar' => 'رقم الهاتف',
                                    'fr' => 'Numéro de téléphone',
                                    'es' => 'Número de teléfono',
                                    'de' => 'Telefonnummer',
                                    'it' => 'Numero di telefono',
                                    'pt' => 'Número de telefone',
                                    'ru' => 'Номер телефона',
                                    'hi' => 'फ़ोन नंबर',
                                ],
                            ],
                            [
                                'type' => 'text',
                                'key' => 'subject',
                                'is_required' => true,
                                'validation' => ['max_length' => 160],
                                'label' => [
                                    'en' => 'Subject',
                                    'ar' => 'الموضوع',
                                    'fr' => 'Objet',
                                    'es' => 'Asunto',
                                    'de' => 'Betreff',
                                    'it' => 'Oggetto',
                                    'pt' => 'Assunto',
                                    'ru' => 'Тема',
                                    'hi' => 'विषय',
                                ],
                            ],
                            [
                                'type' => 'textarea',
                                'key' => 'message',
                                'is_required' => true,
                                'settings' => ['rows' => 8],
                                'validation' => ['max_length' => 4000],
                                'label' => [
                                    'en' => 'Message',
                                    'ar' => 'الرسالة',
                                    'fr' => 'Message',
                                    'es' => 'Mensaje',
                                    'de' => 'Nachricht',
                                    'it' => 'Messaggio',
                                    'pt' => 'Mensagem',
                                    'ru' => 'Сообщение',
                                    'hi' => 'संदेश',
                                ],
                            ],
                            [
                                'type' => 'button',
                                'key' => 'submit',
                                'settings' => ['action' => 'submit', 'variant' => 'primary'],
                                'label' => [
                                    'en' => 'Send message',
                                    'ar' => 'إرسال الرسالة',
                                    'fr' => 'Envoyer le message',
                                    'es' => 'Enviar mensaje',
                                    'de' => 'Nachricht senden',
                                    'it' => 'Invia il messaggio',
                                    'pt' => 'Enviar mensagem',
                                    'ru' => 'Отправить сообщение',
                                    'hi' => 'संदेश भेजें',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'inquiries',
                'name' => 'Admissions inquiry',
                'title' => [
                    'en' => 'Admissions inquiry',
                    'ar' => 'طلب القبول والتسجيل',
                    'fr' => 'Demande d\'admission',
                    'es' => 'Solicitud de admisión',
                    'de' => 'Aufnahmeanfrage',
                    'it' => 'Richiesta di ammissione',
                    'pt' => 'Pedido de admissão',
                    'ru' => 'Заявка на приём',
                    'hi' => 'प्रवेश पूछताछ',
                ],
                'confirmation_message' => [
                    'en' => 'Thank you. Our admissions team will be in touch.',
                    'ar' => 'شكرًا لك. سيتواصل معك فريق القبول والتسجيل.',
                    'fr' => 'Merci. Notre équipe des admissions vous contactera.',
                    'es' => 'Gracias. Nuestro equipo de admisiones se pondrá en contacto.',
                    'de' => 'Vielen Dank. Unser Aufnahmeteam wird sich melden.',
                    'it' => 'Grazie. Il nostro ufficio ammissioni ti contatterà.',
                    'pt' => 'Obrigado. A nossa equipa de admissões entrará em contacto.',
                    'ru' => 'Спасибо. Приёмная комиссия свяжется с вами.',
                    'hi' => 'धन्यवाद। हमारी प्रवेश टीम आपसे संपर्क करेगी।',
                ],
                'pages' => [
                    [
                        'name' => 'Inquiry',
                        'fields' => [
                            [
                                'type' => 'text',
                                'key' => 'guardian_name',
                                'is_required' => true,
                                'validation' => ['max_length' => 120],
                                'label' => [
                                    'en' => 'Guardian name',
                                    'ar' => 'اسم ولي الأمر',
                                    'fr' => 'Nom du responsable légal',
                                    'es' => 'Nombre del tutor',
                                    'de' => 'Name des Erziehungsberechtigten',
                                    'it' => 'Nome del tutore',
                                    'pt' => 'Nome do encarregado de educação',
                                    'ru' => 'Имя родителя или опекуна',
                                    'hi' => 'अभिभावक का नाम',
                                ],
                            ],
                            [
                                'type' => 'email',
                                'key' => 'email',
                                'is_required' => true,
                                'label' => [
                                    'en' => 'Email address',
                                    'ar' => 'البريد الإلكتروني',
                                    'fr' => 'Adresse e-mail',
                                    'es' => 'Correo electrónico',
                                    'de' => 'E-Mail-Adresse',
                                    'it' => 'Indirizzo e-mail',
                                    'pt' => 'Endereço de e-mail',
                                    'ru' => 'Электронная почта',
                                    'hi' => 'ईमेल पता',
                                ],
                            ],
                            [
                                'type' => 'phone',
                                'key' => 'phone',
                                'is_required' => true,
                                'label' => [
                                    'en' => 'Phone number',
                                    'ar' => 'رقم الهاتف',
                                    'fr' => 'Numéro de téléphone',
                                    'es' => 'Número de teléfono',
                                    'de' => 'Telefonnummer',
                                    'it' => 'Numero di telefono',
                                    'pt' => 'Número de telefone',
                                    'ru' => 'Номер телефона',
                                    'hi' => 'फ़ोन नंबर',
                                ],
                            ],
                            [
                                'type' => 'text',
                                'key' => 'student_name',
                                'is_required' => true,
                                'validation' => ['max_length' => 120],
                                'label' => [
                                    'en' => 'Student name',
                                    'ar' => 'اسم الطالب',
                                    'fr' => 'Nom de l\'élève',
                                    'es' => 'Nombre del alumno',
                                    'de' => 'Name des Schülers',
                                    'it' => 'Nome dello studente',
                                    'pt' => 'Nome do aluno',
                                    'ru' => 'Имя учащегося',
                                    'hi' => 'छात्र का नाम',
                                ],
                            ],
                            // 'today' is resolved to a concrete date by DateType::resolve() at
                            // render time — a relative string cannot be compared against a
                            // date_format rule, which is why it resolves there.
                            [
                                'type' => 'date',
                                'key' => 'student_birthdate',
                                'is_required' => true,
                                'validation' => ['max_date' => 'today'],
                                'label' => [
                                    'en' => 'Student date of birth',
                                    'ar' => 'تاريخ ميلاد الطالب',
                                    'fr' => 'Date de naissance de l\'élève',
                                    'es' => 'Fecha de nacimiento del alumno',
                                    'de' => 'Geburtsdatum des Schülers',
                                    'it' => 'Data di nascita dello studente',
                                    'pt' => 'Data de nascimento do aluno',
                                    'ru' => 'Дата рождения учащегося',
                                    'hi' => 'छात्र की जन्म तिथि',
                                ],
                            ],
                            [
                                'type' => 'text',
                                'key' => 'student_school',
                                'validation' => ['max_length' => 160],
                                'label' => [
                                    'en' => 'Current school',
                                    'ar' => 'المدرسة الحالية',
                                    'fr' => 'École actuelle',
                                    'es' => 'Centro actual',
                                    'de' => 'Derzeitige Schule',
                                    'it' => 'Scuola attuale',
                                    'pt' => 'Escola atual',
                                    'ru' => 'Текущая школа',
                                    'hi' => 'वर्तमान विद्यालय',
                                ],
                            ],
                            [
                                'type' => 'select',
                                'key' => 'academic_year',
                                'is_required' => true,
                                // A fixed list rather than a generated one: a seeder runs once, and
                                // a range computed from the seed date would silently go stale.
                                // Extend it here.
                                //
                                // No `label`: a year span is a code that reads the same in every
                                // language, so the VALUE is the label and the seeder writes it to
                                // all nine locales — an admin who wants a Hijri annotation in
                                // Arabic then has a row to edit rather than a blank to discover.
                                'options' => [
                                    ['value' => '2026/2027'],
                                    ['value' => '2027/2028'],
                                    ['value' => '2028/2029'],
                                    ['value' => '2029/2030'],
                                    ['value' => '2030/2031'],
                                ],
                                'label' => [
                                    'en' => 'Academic year',
                                    'ar' => 'العام الدراسي',
                                    'fr' => 'Année scolaire',
                                    'es' => 'Curso académico',
                                    'de' => 'Schuljahr',
                                    'it' => 'Anno scolastico',
                                    'pt' => 'Ano letivo',
                                    'ru' => 'Учебный год',
                                    'hi' => 'शैक्षणिक वर्ष',
                                ],
                            ],
                            [
                                'type' => 'select',
                                'key' => 'grade',
                                'is_required' => true,
                                // The shared 15-grade list — see gradeOptions().
                                'options' => $this->gradeOptions(),
                                'label' => [
                                    'en' => 'Grade applied for',
                                    'ar' => 'الصف المتقدَّم إليه',
                                    'fr' => 'Niveau demandé',
                                    'es' => 'Curso solicitado',
                                    'de' => 'Gewünschte Klassenstufe',
                                    'it' => 'Classe richiesta',
                                    'pt' => 'Ano pretendido',
                                    'ru' => 'Желаемый класс',
                                    'hi' => 'आवेदित कक्षा',
                                ],
                            ],
                            [
                                'type' => 'textarea',
                                'key' => 'questions',
                                'settings' => ['rows' => 5],
                                'validation' => ['max_length' => 4000],
                                'label' => [
                                    'en' => 'Questions',
                                    'ar' => 'أسئلتك',
                                    'fr' => 'Questions',
                                    'es' => 'Preguntas',
                                    'de' => 'Fragen',
                                    'it' => 'Domande',
                                    'pt' => 'Questões',
                                    'ru' => 'Вопросы',
                                    'hi' => 'प्रश्न',
                                ],
                            ],
                            [
                                'type' => 'button',
                                'key' => 'submit',
                                'settings' => ['action' => 'submit', 'variant' => 'primary'],
                                'label' => [
                                    'en' => 'Send inquiry',
                                    'ar' => 'إرسال الطلب',
                                    'fr' => 'Envoyer la demande',
                                    'es' => 'Enviar solicitud',
                                    'de' => 'Anfrage senden',
                                    'it' => 'Invia la richiesta',
                                    'pt' => 'Enviar pedido',
                                    'ru' => 'Отправить заявку',
                                    'hi' => 'पूछताछ भेजें',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            /*
             * The job application.
             *
             * FIVE STEPS: who you are, then ONE STEP PER REPEATABLE SECTION. Packing
             * the sections together fails on height — education, experience and
             * languages each grow to ten rows or more, so a shared page's length is
             * set by the applicant rather than by the form, and the section below is
             * pushed off-screen by the one above it. The cost is that the server
             * validates on submit, so a rejected application can reopen several
             * screens from the mistake; that is the trade, and the groups are what
             * make it worth paying.
             *
             * The CV sits LAST on the first page: someone says who they are, then
             * attaches the file. Its position is free — the parse-and-prefill flow
             * uploads through the chooser BEFORE the renderer mounts and hands it
             * initial `values`, so it never reads this field's place in the page.
             *
             * `job_offer_id` is hidden and carries which posting this is against. It is
             * visitor-tamperable, so ApplicationProjector re-reads the posting rather
             * than trusting it, and an absent value falls back to the seeded
             * general-application posting.
             */
            [
                'slug' => 'job-application',
                'name' => 'Job application',
                'title' => [
                    'en' => 'Apply', 'ar' => 'تقديم طلب', 'fr' => 'Postuler', 'es' => 'Solicitar',
                    'de' => 'Bewerben', 'it' => 'Candidati', 'pt' => 'Candidatar-se',
                    'ru' => 'Подать заявку', 'hi' => 'आवेदन करें',
                ],
                'confirmation_message' => [
                    'en' => 'Thank you. We have received your application and will be in touch if there is a suitable opening.',
                    'ar' => 'شكرًا لك. لقد تلقينا طلبك وسنتواصل معك في حال توفر وظيفة مناسبة.',
                    'fr' => 'Merci. Nous avons bien reçu votre candidature et vous contacterons si un poste correspond.',
                    'es' => 'Gracias. Hemos recibido su candidatura y le contactaremos si hay una vacante adecuada.',
                    'de' => 'Vielen Dank. Wir haben Ihre Bewerbung erhalten und melden uns bei einer passenden Stelle.',
                    'it' => 'Grazie. Abbiamo ricevuto la tua candidatura e ti contatteremo per una posizione adatta.',
                    'pt' => 'Obrigado. Recebemos a sua candidatura e entraremos em contacto se houver uma vaga adequada.',
                    'ru' => 'Спасибо. Мы получили вашу заявку и свяжемся с вами при наличии подходящей вакансии.',
                    'hi' => 'धन्यवाद। हमें आपका आवेदन मिल गया है और उपयुक्त रिक्ति होने पर हम संपर्क करेंगे।',
                ],
                'pages' => [
                    [
                        'name' => 'Personal',
                        'title' => [
                            'en' => 'Your details',
                            'ar' => 'بياناتك',
                            'fr' => 'Vos informations',
                            'es' => 'Sus datos',
                            'de' => 'Ihre Angaben',
                            'it' => 'I tuoi dati',
                            'pt' => 'Os seus dados',
                            'ru' => 'Ваши данные',
                            'hi' => 'आपका विवरण',
                        ],
                        'fields' => [
                            [
                                'type' => 'text', 'key' => 'first_name', 'is_required' => true,
                                'settings' => ['width' => '50'],
                                'validation' => ['max_length' => 100],
                                'label' => [
                                    'en' => 'First name', 'ar' => 'الاسم الأول', 'fr' => 'Prénom',
                                    'es' => 'Nombre', 'de' => 'Vorname', 'it' => 'Nome',
                                    'pt' => 'Nome próprio', 'ru' => 'Имя', 'hi' => 'पहला नाम',
                                ],
                            ],
                            [
                                'type' => 'text', 'key' => 'last_name', 'is_required' => true,
                                'settings' => ['width' => '50'],
                                'validation' => ['max_length' => 100],
                                'label' => [
                                    'en' => 'Last name', 'ar' => 'اسم العائلة', 'fr' => 'Nom',
                                    'es' => 'Apellidos', 'de' => 'Nachname', 'it' => 'Cognome',
                                    'pt' => 'Apelido', 'ru' => 'Фамилия', 'hi' => 'उपनाम',
                                ],
                            ],
                            [
                                'type' => 'email', 'key' => 'email', 'is_required' => true,
                                'settings' => ['width' => '50'],
                                'label' => [
                                    'en' => 'Email', 'ar' => 'البريد الإلكتروني', 'fr' => 'E-mail',
                                    'es' => 'Correo electrónico', 'de' => 'E-Mail', 'it' => 'E-mail',
                                    'pt' => 'E-mail', 'ru' => 'Эл. почта', 'hi' => 'ईमेल',
                                ],
                            ],
                            [
                                'type' => 'phone', 'key' => 'phone', 'is_required' => true,
                                'settings' => ['width' => '50'],
                                'label' => [
                                    'en' => 'Phone', 'ar' => 'رقم الهاتف', 'fr' => 'Téléphone',
                                    'es' => 'Teléfono', 'de' => 'Telefon', 'it' => 'Telefono',
                                    'pt' => 'Telefone', 'ru' => 'Телефон', 'hi' => 'फ़ोन',
                                ],
                            ],
                            [
                                // The ISO code is the answer, so a nationality means the
                                // same thing whichever language it was chosen in.
                                'type' => 'select', 'key' => 'nationality', 'is_required' => true,
                                'options_from' => 'countries',
                                'label' => [
                                    'en' => 'Nationality', 'ar' => 'الجنسية', 'fr' => 'Nationalité',
                                    'es' => 'Nacionalidad', 'de' => 'Staatsangehörigkeit', 'it' => 'Nazionalità',
                                    'pt' => 'Nacionalidade', 'ru' => 'Гражданство', 'hi' => 'राष्ट्रीयता',
                                ],
                            ],
                            [
                                'type' => 'textarea', 'key' => 'address',
                                'settings' => ['rows' => 3],
                                'validation' => ['max_length' => 500],
                                'label' => [
                                    'en' => 'Address', 'ar' => 'العنوان', 'fr' => 'Adresse',
                                    'es' => 'Dirección', 'de' => 'Adresse', 'it' => 'Indirizzo',
                                    'pt' => 'Morada', 'ru' => 'Адрес', 'hi' => 'पता',
                                ],
                            ],
                            [
                                // The one split left that a page boundary does not
                                // make: attaching a file is a different act from
                                // typing, and it closes the page rather than opening
                                // one of its own.
                                //
                                // DELIBERATELY UNCAPTIONED. The field immediately
                                // below is labelled "Curriculum vitae", so a caption
                                // saying it too would print the same words twice in a
                                // row — the failure that made FormRenderer stop
                                // printing the form's own title above its first input.
                                // A bare rule is the break; the field names itself.
                                'type' => 'separator', 'key' => 'sep_cv',
                                'settings' => ['spacing' => 'normal'],
                            ],
                            [
                                'type' => 'file', 'key' => 'cv', 'is_required' => true,
                                'settings' => ['is_multiple' => false, 'max_files' => 1, 'extensions' => ['pdf', 'doc', 'docx']],
                                'label' => [
                                    'en' => 'Curriculum vitae', 'ar' => 'السيرة الذاتية', 'fr' => 'CV',
                                    'es' => 'Currículum', 'de' => 'Lebenslauf', 'it' => 'Curriculum',
                                    'pt' => 'Currículo', 'ru' => 'Резюме', 'hi' => 'बायोडाटा',
                                ],
                            ],
                            ['type' => 'hidden', 'key' => 'job_offer_id'],
                            [
                                'type' => 'button', 'key' => 'to_education',
                                'settings' => ['action' => 'next', 'variant' => 'primary'],
                                'label' => [
                                    'en' => 'Continue', 'ar' => 'متابعة', 'fr' => 'Continuer',
                                    'es' => 'Continuar', 'de' => 'Weiter', 'it' => 'Continua',
                                    'pt' => 'Continuar', 'ru' => 'Продолжить', 'hi' => 'जारी रखें',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Education',
                        'title' => [
                            'en' => 'Education',
                            'ar' => 'المؤهلات العلمية',
                            'fr' => 'Formation',
                            'es' => 'Formación',
                            'de' => 'Ausbildung',
                            'it' => 'Formazione',
                            'pt' => 'Formação',
                            'ru' => 'Образование',
                            'hi' => 'शिक्षा',
                        ],
                        'fields' => [
                            [
                                /*
                                 * NOT REQUIRED, and no minimum row. The school hires
                                 * across every role — teaching, admin, cleaning,
                                 * maintenance, drivers — and demanding a qualification
                                 * before someone can apply for a manual post turns the
                                 * form into a filter nobody intended.
                                 */
                                'type' => 'group', 'key' => 'education', 'is_required' => false,
                                'settings' => ['min_instances' => 0, 'max_instances' => 10],
                                'label' => [
                                    'en' => 'Education', 'ar' => 'المؤهلات العلمية', 'fr' => 'Formation',
                                    'es' => 'Formación', 'de' => 'Ausbildung', 'it' => 'Formazione',
                                    'pt' => 'Formação', 'ru' => 'Образование', 'hi' => 'शिक्षा',
                                ],
                                'children' => [
                                    [
                                        'type' => 'text', 'key' => 'institution', 'is_required' => true,
                                        'label' => [
                                            'en' => 'Institution', 'ar' => 'المؤسسة التعليمية', 'fr' => 'Établissement',
                                            'es' => 'Institución', 'de' => 'Einrichtung', 'it' => 'Istituto',
                                            'pt' => 'Instituição', 'ru' => 'Учебное заведение', 'hi' => 'संस्थान',
                                        ],
                                    ],
                                    [
                                        'type' => 'text', 'key' => 'degree',
                                        'settings' => ['width' => '50'],
                                        'label' => [
                                            'en' => 'Degree', 'ar' => 'الدرجة العلمية', 'fr' => 'Diplôme',
                                            'es' => 'Titulación', 'de' => 'Abschluss', 'it' => 'Titolo',
                                            'pt' => 'Grau', 'ru' => 'Степень', 'hi' => 'डिग्री',
                                        ],
                                    ],
                                    [
                                        'type' => 'text', 'key' => 'field_of_study',
                                        'settings' => ['width' => '50'],
                                        'label' => [
                                            'en' => 'Field of study', 'ar' => 'مجال الدراسة', 'fr' => 'Domaine d’études',
                                            'es' => 'Campo de estudio', 'de' => 'Fachrichtung', 'it' => 'Ambito di studio',
                                            'pt' => 'Área de estudo', 'ru' => 'Специальность', 'hi' => 'अध्ययन क्षेत्र',
                                        ],
                                    ],
                                    [
                                        'type' => 'number', 'key' => 'start_year',
                                        'settings' => ['width' => '50'],
                                        'validation' => ['min' => 1950, 'max' => 2100, 'integer_only' => true],
                                        'label' => [
                                            'en' => 'Start year', 'ar' => 'سنة البدء', 'fr' => 'Année de début',
                                            'es' => 'Año de inicio', 'de' => 'Startjahr', 'it' => 'Anno di inizio',
                                            'pt' => 'Ano de início', 'ru' => 'Год начала', 'hi' => 'प्रारंभ वर्ष',
                                        ],
                                    ],
                                    [
                                        'type' => 'number', 'key' => 'end_year',
                                        'settings' => ['width' => '50'],
                                        'validation' => ['min' => 1950, 'max' => 2100, 'integer_only' => true],
                                        'label' => [
                                            'en' => 'End year', 'ar' => 'سنة الانتهاء', 'fr' => 'Année de fin',
                                            'es' => 'Año de fin', 'de' => 'Endjahr', 'it' => 'Anno di fine',
                                            'pt' => 'Ano de fim', 'ru' => 'Год окончания', 'hi' => 'समाप्ति वर्ष',
                                        ],
                                    ],
                                    [
                                        'type' => 'textarea', 'key' => 'achievements',
                                        'settings' => ['rows' => 2],
                                        'validation' => ['max_length' => 1000],
                                        'label' => [
                                            'en' => 'Achievements', 'ar' => 'الإنجازات', 'fr' => 'Résultats obtenus',
                                            'es' => 'Logros', 'de' => 'Erreichtes', 'it' => 'Risultati',
                                            'pt' => 'Conquistas', 'ru' => 'Достижения', 'hi' => 'उपलब्धियाँ',
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'button', 'key' => 'back_to_details',
                                'settings' => ['action' => 'back', 'variant' => 'secondary'],
                                'label' => [
                                    'en' => 'Back', 'ar' => 'رجوع', 'fr' => 'Retour', 'es' => 'Atrás',
                                    'de' => 'Zurück', 'it' => 'Indietro', 'pt' => 'Voltar',
                                    'ru' => 'Назад', 'hi' => 'वापस',
                                ],
                            ],
                            [
                                'type' => 'button', 'key' => 'to_experience',
                                'settings' => ['action' => 'next', 'variant' => 'primary'],
                                'label' => [
                                    'en' => 'Continue', 'ar' => 'متابعة', 'fr' => 'Continuer',
                                    'es' => 'Continuar', 'de' => 'Weiter', 'it' => 'Continua',
                                    'pt' => 'Continuar', 'ru' => 'Продолжить', 'hi' => 'जारी रखें',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Experience',
                        'title' => [
                            'en' => 'Work experience',
                            'ar' => 'الخبرة العملية',
                            'fr' => 'Expérience professionnelle',
                            'es' => 'Experiencia laboral',
                            'de' => 'Berufserfahrung',
                            'it' => 'Esperienza lavorativa',
                            'pt' => 'Experiência profissional',
                            'ru' => 'Опыт работы',
                            'hi' => 'कार्य अनुभव',
                        ],
                        'fields' => [
                            [
                                'type' => 'group', 'key' => 'experience',
                                'settings' => ['min_instances' => 0, 'max_instances' => 15],
                                'label' => [
                                    'en' => 'Work experience', 'ar' => 'الخبرة العملية', 'fr' => 'Expérience professionnelle',
                                    'es' => 'Experiencia laboral', 'de' => 'Berufserfahrung', 'it' => 'Esperienza lavorativa',
                                    'pt' => 'Experiência profissional', 'ru' => 'Опыт работы', 'hi' => 'कार्य अनुभव',
                                ],
                                'children' => [
                                    [
                                        'type' => 'text', 'key' => 'company_name', 'is_required' => true,
                                        'settings' => ['width' => '50'],
                                        'label' => [
                                            'en' => 'Employer', 'ar' => 'جهة العمل', 'fr' => 'Employeur',
                                            'es' => 'Empleador', 'de' => 'Arbeitgeber', 'it' => 'Datore di lavoro',
                                            'pt' => 'Empregador', 'ru' => 'Работодатель', 'hi' => 'नियोक्ता',
                                        ],
                                    ],
                                    [
                                        'type' => 'text', 'key' => 'job_title',
                                        'settings' => ['width' => '50'],
                                        'label' => [
                                            'en' => 'Job title', 'ar' => 'المسمى الوظيفي', 'fr' => 'Intitulé du poste',
                                            'es' => 'Puesto', 'de' => 'Position', 'it' => 'Ruolo',
                                            'pt' => 'Cargo', 'ru' => 'Должность', 'hi' => 'पद',
                                        ],
                                    ],
                                    [
                                        'type' => 'number', 'key' => 'from_year',
                                        'settings' => ['width' => '50'],
                                        'validation' => ['min' => 1950, 'max' => 2100, 'integer_only' => true],
                                        'label' => [
                                            'en' => 'Start year', 'ar' => 'سنة البدء', 'fr' => 'Année de début',
                                            'es' => 'Año de inicio', 'de' => 'Startjahr', 'it' => 'Anno di inizio',
                                            'pt' => 'Ano de início', 'ru' => 'Год начала', 'hi' => 'प्रारंभ वर्ष',
                                        ],
                                    ],
                                    [
                                        'type' => 'number', 'key' => 'to_year',
                                        'settings' => ['width' => '50'],
                                        'validation' => ['min' => 1950, 'max' => 2100, 'integer_only' => true],
                                        'label' => [
                                            'en' => 'End year', 'ar' => 'سنة الانتهاء', 'fr' => 'Année de fin',
                                            'es' => 'Año de fin', 'de' => 'Endjahr', 'it' => 'Anno di fine',
                                            'pt' => 'Ano de fim', 'ru' => 'Год окончания', 'hi' => 'समाप्ति वर्ष',
                                        ],
                                    ],
                                    [
                                        'type' => 'textarea', 'key' => 'responsibilities',
                                        'settings' => ['rows' => 3],
                                        'validation' => ['max_length' => 1500],
                                        'label' => [
                                            'en' => 'Responsibilities', 'ar' => 'المهام والمسؤوليات',
                                            'fr' => 'Responsabilités', 'es' => 'Responsabilidades',
                                            'de' => 'Aufgaben', 'it' => 'Responsabilità',
                                            'pt' => 'Responsabilidades', 'ru' => 'Обязанности',
                                            'hi' => 'ज़िम्मेदारियाँ',
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'button', 'key' => 'back_to_education',
                                'settings' => ['action' => 'back', 'variant' => 'secondary'],
                                'label' => [
                                    'en' => 'Back', 'ar' => 'رجوع', 'fr' => 'Retour', 'es' => 'Atrás',
                                    'de' => 'Zurück', 'it' => 'Indietro', 'pt' => 'Voltar',
                                    'ru' => 'Назад', 'hi' => 'वापस',
                                ],
                            ],
                            [
                                'type' => 'button', 'key' => 'to_languages',
                                'settings' => ['action' => 'next', 'variant' => 'primary'],
                                'label' => [
                                    'en' => 'Continue', 'ar' => 'متابعة', 'fr' => 'Continuer',
                                    'es' => 'Continuar', 'de' => 'Weiter', 'it' => 'Continua',
                                    'pt' => 'Continuar', 'ru' => 'Продолжить', 'hi' => 'जारी रखें',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Languages',
                        'title' => [
                            'en' => 'Languages',
                            'ar' => 'اللغات',
                            'fr' => 'Langues',
                            'es' => 'Idiomas',
                            'de' => 'Sprachen',
                            'it' => 'Lingue',
                            'pt' => 'Idiomas',
                            'ru' => 'Языки',
                            'hi' => 'भाषाएँ',
                        ],
                        'fields' => [
                            [
                                'type' => 'group', 'key' => 'languages',
                                'settings' => ['min_instances' => 0, 'max_instances' => 10],
                                'label' => [
                                    'en' => 'Languages', 'ar' => 'اللغات', 'fr' => 'Langues',
                                    'es' => 'Idiomas', 'de' => 'Sprachen', 'it' => 'Lingue',
                                    'pt' => 'Idiomas', 'ru' => 'Языки', 'hi' => 'भाषाएँ',
                                ],
                                'children' => [
                                    [
                                        'type' => 'text', 'key' => 'name', 'is_required' => true,
                                        'settings' => ['width' => '50'],
                                        'label' => [
                                            'en' => 'Language', 'ar' => 'اللغة', 'fr' => 'Langue',
                                            'es' => 'Idioma', 'de' => 'Sprache', 'it' => 'Lingua',
                                            'pt' => 'Idioma', 'ru' => 'Язык', 'hi' => 'भाषा',
                                        ],
                                    ],
                                    [
                                        // Values mirror Candidate::PROFICIENCIES, so the
                                        // projector writes them straight through.
                                        'type' => 'select', 'key' => 'proficiency',
                                        'settings' => ['width' => '50'],
                                        'label' => [
                                            'en' => 'Level', 'ar' => 'المستوى', 'fr' => 'Niveau',
                                            'es' => 'Nivel', 'de' => 'Niveau', 'it' => 'Livello',
                                            'pt' => 'Nível', 'ru' => 'Уровень', 'hi' => 'स्तर',
                                        ],
                                        'options' => [
                                            ['value' => 'basic', 'label' => [
                                                'en' => 'Basic', 'ar' => 'مبتدئ', 'fr' => 'Débutant', 'es' => 'Básico',
                                                'de' => 'Grundkenntnisse', 'it' => 'Base', 'pt' => 'Básico',
                                                'ru' => 'Базовый', 'hi' => 'बुनियादी',
                                            ]],
                                            ['value' => 'intermediate', 'label' => [
                                                'en' => 'Intermediate', 'ar' => 'متوسط', 'fr' => 'Intermédiaire',
                                                'es' => 'Intermedio', 'de' => 'Mittelstufe', 'it' => 'Intermedio',
                                                'pt' => 'Intermédio', 'ru' => 'Средний', 'hi' => 'मध्यम',
                                            ]],
                                            ['value' => 'advanced', 'label' => [
                                                'en' => 'Advanced', 'ar' => 'متقدم', 'fr' => 'Avancé', 'es' => 'Avanzado',
                                                'de' => 'Fortgeschritten', 'it' => 'Avanzato', 'pt' => 'Avançado',
                                                'ru' => 'Продвинутый', 'hi' => 'उन्नत',
                                            ]],
                                            ['value' => 'native', 'label' => [
                                                'en' => 'Native', 'ar' => 'اللغة الأم', 'fr' => 'Langue maternelle',
                                                'es' => 'Nativo', 'de' => 'Muttersprache', 'it' => 'Madrelingua',
                                                'pt' => 'Nativo', 'ru' => 'Родной', 'hi' => 'मातृभाषा',
                                            ]],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'button', 'key' => 'back_to_experience',
                                'settings' => ['action' => 'back', 'variant' => 'secondary'],
                                'label' => [
                                    'en' => 'Back', 'ar' => 'رجوع', 'fr' => 'Retour', 'es' => 'Atrás',
                                    'de' => 'Zurück', 'it' => 'Indietro', 'pt' => 'Voltar',
                                    'ru' => 'Назад', 'hi' => 'वापस',
                                ],
                            ],
                            [
                                'type' => 'button', 'key' => 'to_skills',
                                'settings' => ['action' => 'next', 'variant' => 'primary'],
                                'label' => [
                                    'en' => 'Continue', 'ar' => 'متابعة', 'fr' => 'Continuer',
                                    'es' => 'Continuar', 'de' => 'Weiter', 'it' => 'Continua',
                                    'pt' => 'Continuar', 'ru' => 'Продолжить', 'hi' => 'जारी रखें',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Skills',
                        'title' => [
                            'en' => 'Skills',
                            'ar' => 'المهارات',
                            'fr' => 'Compétences',
                            'es' => 'Competencias',
                            'de' => 'Fähigkeiten',
                            'it' => 'Competenze',
                            'pt' => 'Competências',
                            'ru' => 'Навыки',
                            'hi' => 'कौशल',
                        ],
                        'fields' => [
                            [
                                'type' => 'tags', 'key' => 'skills',
                                'validation' => ['max_items' => 20, 'max_length' => 60],
                                'label' => [
                                    'en' => 'Skills', 'ar' => 'المهارات', 'fr' => 'Compétences',
                                    'es' => 'Competencias', 'de' => 'Fähigkeiten', 'it' => 'Competenze',
                                    'pt' => 'Competências', 'ru' => 'Навыки', 'hi' => 'कौशल',
                                ],
                                'placeholder' => [
                                    'en' => 'Type a skill and press Enter', 'ar' => 'اكتب مهارة ثم اضغط Enter',
                                    'fr' => 'Saisissez une compétence puis Entrée', 'es' => 'Escriba una competencia y pulse Intro',
                                    'de' => 'Fähigkeit eingeben und Enter drücken', 'it' => 'Digita una competenza e premi Invio',
                                    'pt' => 'Escreva uma competência e prima Enter', 'ru' => 'Введите навык и нажмите Enter',
                                    'hi' => 'कौशल लिखें और Enter दबाएँ',
                                ],
                            ],
                            [
                                'type' => 'button', 'key' => 'back_to_languages',
                                'settings' => ['action' => 'back', 'variant' => 'secondary'],
                                'label' => [
                                    'en' => 'Back', 'ar' => 'رجوع', 'fr' => 'Retour', 'es' => 'Atrás',
                                    'de' => 'Zurück', 'it' => 'Indietro', 'pt' => 'Voltar',
                                    'ru' => 'Назад', 'hi' => 'वापस',
                                ],
                            ],
                            [
                                'type' => 'button', 'key' => 'submit_application',
                                'settings' => ['action' => 'submit', 'variant' => 'primary'],
                                'label' => [
                                    'en' => 'Submit application', 'ar' => 'إرسال الطلب',
                                    'fr' => 'Envoyer la candidature', 'es' => 'Enviar candidatura',
                                    'de' => 'Bewerbung senden', 'it' => 'Invia candidatura',
                                    'pt' => 'Enviar candidatura', 'ru' => 'Отправить заявку',
                                    'hi' => 'आवेदन भेजें',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            /*
             * The school-visit reservation.
             *
             * TWO PAGES: who you are, then who is coming. The wizard on /visits has
             * already asked WHICH visit, WHICH time and HOW MANY people before this
             * form mounts, so those are the only two questions left — and they are
             * genuinely different ones, asked of different people. Splitting them
             * also keeps the students group off the screen that holds the contact
             * details, which matters because the group grows: five children is
             * twenty inputs, and a shared page's length would be set by the party
             * size rather than by the form.
             *
             * THREE HIDDEN FIELDS carry what the wizard chose. Hidden rather than
             * absent because the submit is a plain POST and those choices have to
             * travel with the answers — site/visits.js mounts the renderer with them
             * as initial values, the way the CV chooser hands it parsed ones. All
             * three are visitor-tamperable, so VisitReservationIsAllowed re-reads the
             * slot and the service instead of trusting them, and ReservationProjector
             * takes the service off the SLOT so the two cannot disagree.
             *
             * NONE OF THE THREE IS REQUIRED at the schema level, deliberately: an
             * empty slot id would otherwise be reported as "The visit slot id field is
             * required", naming an input the visitor cannot see. The rule answers the
             * same case with a sentence about time slots.
             *
             * THE STUDENT KEYS ARE PREFIXED because field keys are unique per FORM,
             * group children included — the guardian already holds first_name and
             * last_name. config/visits.php maps them back onto the shared columns;
             * that is exactly what the map is for.
             *
             * max_instances mirrors the default VisitService::max_visitors. It is the
             * ceiling a hand-rolled post cannot exceed; the real per-booking cap is
             * the party size the visitor picked on the card, enforced at submit as a
             * max on this group.
             */
            [
                'slug' => 'visit-reservation',
                'name' => 'Visit reservation',
                'title' => [
                    'en' => 'Book a visit',
                    'ar' => 'حجز زيارة',
                    'fr' => 'Réserver une visite',
                    'es' => 'Reservar una visita',
                    'de' => 'Besuch buchen',
                    'it' => 'Prenota una visita',
                    'pt' => 'Marcar uma visita',
                    'ru' => 'Записаться на визит',
                    'hi' => 'भ्रमण बुक करें',
                ],
                'confirmation_message' => [
                    'en' => 'Thank you. Your visit is booked and we will be in touch to confirm.',
                    'ar' => 'شكرًا لك. تم حجز زيارتك وسنتواصل معك للتأكيد.',
                    'fr' => 'Merci. Votre visite est réservée et nous vous contacterons pour la confirmer.',
                    'es' => 'Gracias. Su visita está reservada y nos pondremos en contacto para confirmarla.',
                    'de' => 'Vielen Dank. Ihr Besuch ist gebucht und wir melden uns zur Bestätigung.',
                    'it' => 'Grazie. La tua visita è prenotata e ti contatteremo per confermarla.',
                    'pt' => 'Obrigado. A sua visita está marcada e entraremos em contacto para confirmar.',
                    'ru' => 'Спасибо. Ваш визит забронирован, мы свяжемся с вами для подтверждения.',
                    'hi' => 'धन्यवाद। आपका भ्रमण बुक हो गया है और हम पुष्टि के लिए संपर्क करेंगे।',
                ],
                'pages' => [
                    [
                        'name' => 'Details',
                        'title' => [
                            'en' => 'Your details',
                            'ar' => 'بياناتك',
                            'fr' => 'Vos coordonnées',
                            'es' => 'Sus datos',
                            'de' => 'Ihre Angaben',
                            'it' => 'I tuoi dati',
                            'pt' => 'Os seus dados',
                            'ru' => 'Ваши данные',
                            'hi' => 'आपका विवरण',
                        ],
                        'fields' => [
                            ['type' => 'hidden', 'key' => 'visit_service_id'],
                            ['type' => 'hidden', 'key' => 'visit_slot_id'],
                            ['type' => 'hidden', 'key' => 'visitors_count'],
                            [
                                'type' => 'text', 'key' => 'first_name', 'is_required' => true,
                                'settings' => ['width' => '50'],
                                'validation' => ['max_length' => 100],
                                'label' => [
                                    'en' => 'First name', 'ar' => 'الاسم الأول', 'fr' => 'Prénom',
                                    'es' => 'Nombre', 'de' => 'Vorname', 'it' => 'Nome',
                                    'pt' => 'Nome próprio', 'ru' => 'Имя', 'hi' => 'पहला नाम',
                                ],
                            ],
                            [
                                'type' => 'text', 'key' => 'last_name', 'is_required' => true,
                                'settings' => ['width' => '50'],
                                'validation' => ['max_length' => 100],
                                'label' => [
                                    'en' => 'Last name', 'ar' => 'اسم العائلة', 'fr' => 'Nom',
                                    'es' => 'Apellidos', 'de' => 'Nachname', 'it' => 'Cognome',
                                    'pt' => 'Apelido', 'ru' => 'Фамилия', 'hi' => 'उपनाम',
                                ],
                            ],
                            [
                                'type' => 'email', 'key' => 'email', 'is_required' => true,
                                'settings' => ['width' => '50'],
                                'label' => [
                                    'en' => 'Email', 'ar' => 'البريد الإلكتروني', 'fr' => 'E-mail',
                                    'es' => 'Correo electrónico', 'de' => 'E-Mail', 'it' => 'E-mail',
                                    'pt' => 'E-mail', 'ru' => 'Эл. почта', 'hi' => 'ईमेल',
                                ],
                            ],
                            [
                                'type' => 'phone', 'key' => 'phone', 'is_required' => true,
                                'settings' => ['width' => '50'],
                                'label' => [
                                    'en' => 'Phone', 'ar' => 'رقم الهاتف', 'fr' => 'Téléphone',
                                    'es' => 'Teléfono', 'de' => 'Telefon', 'it' => 'Telefono',
                                    'pt' => 'Telefone', 'ru' => 'Телефон', 'hi' => 'फ़ोन',
                                ],
                            ],
                            [
                                'type' => 'button', 'key' => 'to_students',
                                'settings' => ['action' => 'next', 'variant' => 'primary'],
                                'label' => [
                                    'en' => 'Continue', 'ar' => 'متابعة', 'fr' => 'Continuer',
                                    'es' => 'Continuar', 'de' => 'Weiter', 'it' => 'Continua',
                                    'pt' => 'Continuar', 'ru' => 'Продолжить', 'hi' => 'जारी रखें',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Students',
                        'title' => [
                            'en' => 'Who is coming',
                            'ar' => 'من سيحضر',
                            'fr' => 'Qui vient',
                            'es' => 'Quién asiste',
                            'de' => 'Wer kommt',
                            'it' => 'Chi partecipa',
                            'pt' => 'Quem vem',
                            'ru' => 'Кто придёт',
                            'hi' => 'कौन आ रहा है',
                        ],
                        'fields' => [
                            [
                                /*
                                 * REQUIRED, with a minimum of one row — unlike the job
                                 * form's optional education. A school visit exists to
                                 * show a child around, so a booking with nobody named
                                 * is a booking the front desk cannot prepare for.
                                 */
                                'type' => 'group', 'key' => 'students', 'is_required' => true,
                                'settings' => ['min_instances' => 1, 'max_instances' => 5],
                                'label' => [
                                    'en' => 'Students', 'ar' => 'الطلاب', 'fr' => 'Élèves',
                                    'es' => 'Alumnos', 'de' => 'Schülerinnen und Schüler',
                                    'it' => 'Studenti', 'pt' => 'Alunos', 'ru' => 'Учащиеся',
                                    'hi' => 'छात्र',
                                ],
                                'children' => [
                                    [
                                        'type' => 'text', 'key' => 'student_first_name', 'is_required' => true,
                                        'settings' => ['width' => '50'],
                                        'validation' => ['max_length' => 100],
                                        'label' => [
                                            'en' => 'First name', 'ar' => 'الاسم الأول', 'fr' => 'Prénom',
                                            'es' => 'Nombre', 'de' => 'Vorname', 'it' => 'Nome',
                                            'pt' => 'Nome próprio', 'ru' => 'Имя', 'hi' => 'पहला नाम',
                                        ],
                                    ],
                                    [
                                        'type' => 'text', 'key' => 'student_last_name', 'is_required' => true,
                                        'settings' => ['width' => '50'],
                                        'validation' => ['max_length' => 100],
                                        'label' => [
                                            'en' => 'Last name', 'ar' => 'اسم العائلة', 'fr' => 'Nom',
                                            'es' => 'Apellidos', 'de' => 'Nachname', 'it' => 'Cognome',
                                            'pt' => 'Apelido', 'ru' => 'Фамилия', 'hi' => 'उपनाम',
                                        ],
                                    ],
                                    [
                                        // The shared 15-grade list — see gradeOptions().
                                        // The year the child is in NOW, at whatever
                                        // school they attend today.
                                        'type' => 'select', 'key' => 'grade', 'is_required' => true,
                                        'settings' => ['width' => '50'],
                                        'options' => $this->gradeOptions(),
                                        'label' => [
                                            'en' => 'Current grade', 'ar' => 'الصف الحالي',
                                            'fr' => 'Niveau actuel', 'es' => 'Curso actual',
                                            'de' => 'Aktuelle Klassenstufe', 'it' => 'Classe attuale',
                                            'pt' => 'Ano atual', 'ru' => 'Текущий класс',
                                            'hi' => 'वर्तमान कक्षा',
                                        ],
                                    ],
                                    [
                                        'type' => 'text', 'key' => 'current_school',
                                        'settings' => ['width' => '50'],
                                        'validation' => ['max_length' => 160],
                                        'label' => [
                                            'en' => 'Current school', 'ar' => 'المدرسة الحالية',
                                            'fr' => 'École actuelle', 'es' => 'Centro actual',
                                            'de' => 'Derzeitige Schule', 'it' => 'Scuola attuale',
                                            'pt' => 'Escola atual', 'ru' => 'Текущая школа',
                                            'hi' => 'वर्तमान विद्यालय',
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'button', 'key' => 'back_to_details',
                                'settings' => ['action' => 'back', 'variant' => 'secondary'],
                                'label' => [
                                    'en' => 'Back', 'ar' => 'رجوع', 'fr' => 'Retour', 'es' => 'Atrás',
                                    'de' => 'Zurück', 'it' => 'Indietro', 'pt' => 'Voltar',
                                    'ru' => 'Назад', 'hi' => 'वापस',
                                ],
                            ],
                            [
                                'type' => 'button', 'key' => 'confirm_booking',
                                'settings' => ['action' => 'submit', 'variant' => 'primary'],
                                'label' => [
                                    'en' => 'Confirm booking', 'ar' => 'تأكيد الحجز',
                                    'fr' => 'Confirmer la réservation', 'es' => 'Confirmar la reserva',
                                    'de' => 'Buchung bestätigen', 'it' => 'Conferma la prenotazione',
                                    'pt' => 'Confirmar a reserva', 'ru' => 'Подтвердить бронирование',
                                    'hi' => 'बुकिंग की पुष्टि करें',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
