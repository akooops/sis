<?php

namespace App\Services\Newsletter;

use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\Language;
use App\Models\NewsletterGroup;

/**
 * Keeps the signup form's mailing-list picker in step with `newsletter_groups`.
 *
 * WHY THIS CLASS EXISTS AT ALL. A builder form's choices are `form_field_options`
 * rows, and FormsSeeder writes them exactly once: `optionsFor()` knows one
 * generated source (countries), and `run()` skips `buildStructure()` unless the
 * Form row `wasRecentlyCreated`. So a mailing list an admin adds next week would
 * never appear in the picker, and — worse — would fail validation if its id were
 * posted anyway, because SelectType/CheckboxType validate membership with
 * `Rule::in($field->options->pluck('value'))` against those same rows. A list
 * that an admin can add is therefore a list something has to sync.
 *
 * ONE WRITER, TWO CALLERS. `options()` is the read model — it is what a group
 * looks like as a form choice — and both the seeder (through
 * `options_from => 'newsletter_groups'`, on a fresh install) and
 * NewsletterGroupObserver (every time afterwards) go through it, so the value
 * and label of an option cannot be spelled two different ways.
 *
 * THE OPTION VALUE IS THE GROUP'S `code`, NOT ITS ULID, and that is a deliberate
 * trade. Whatever goes in `value` is verbatim what lands in
 * `form_submissions.data` — and nothing resolves it back: none of
 * select/checkbox/radio overrides `display()`, so BaseFieldType implodes the raw
 * values into the CSV export, the submission drawer and the notification
 * summary. ULIDs there are unreadable, and they would also make every audit row
 * this class writes read as a wall of ULIDs. The cost of using `code` is that it
 * is admin-editable, so a rename would otherwise look like a delete plus a
 * create — which is what `rename()` exists to prevent.
 */
class FormOptions
{
    /**
     * Every mailing list, as the picker's options.
     *
     * Default-first so the list most people want is the first tick, then by the
     * internal `name` — not by the translated title, which lives in a JSON
     * column where nine locales would need nine orderings and SQL can only have
     * one.
     *
     * @return array<int, array{value: string, label: array<string, string>}>
     */
    public function options(): array
    {
        $codes = Language::query()->pluck('code')->all();

        return NewsletterGroup::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn (NewsletterGroup $group) => [
                'value' => (string) $group->code,
                'label' => $this->label($group, $codes),
            ])
            ->all();
    }

    /**
     * Write the current lists onto the seeded field.
     *
     * Upserts on (form_field_id, value) because that pair is the table's unique
     * index — a blind create would throw a duplicate-entry on the second run.
     *
     * A group that is gone loses its option: leaving it would offer a list
     * nobody can join. Old submissions keep the code they stored, which then
     * points at nothing — acceptable, because the subscriber rows are the record
     * and the submission is the historical copy of what was said.
     */
    public function sync(): void
    {
        $field = $this->field();

        if ($field === null) {
            return;
        }

        $options = $this->options();

        /*
         * NEVER EMPTY A CHOICE FIELD. UpdateBuilderData::declaredRules() requires
         * `min:1` options for any type that has them, and the renderer would draw
         * an unanswerable control. In practice this cannot happen —
         * NewsletterGroupsController refuses to delete the default list — but a
         * sync that could blank the form is not one to leave unguarded.
         */
        if ($options === []) {
            return;
        }

        foreach ($options as $order => $option) {
            $row = FormFieldOption::firstOrNew([
                'form_field_id' => $field->id,
                'value' => $option['value'],
            ]);

            $row->order = $order;

            /*
             * Only when there is something to say. On spatie 6.11.4 assigning a
             * NON-EMPTY array merges per locale, but assigning [] writes a JSON
             * `[]` and wipes the column — so a group with no title in any locale
             * must leave the existing label alone rather than blank it.
             *
             * Merge semantics also mean a locale an admin CLEARS keeps its old
             * option label. That is the lesser of the two evils: a stale label
             * still names the right list, a wiped one names nothing.
             */
            if ($option['label'] !== []) {
                $row->label = $option['label'];
            }

            $row->save();
        }

        // Iterate rather than a builder delete: FormFieldOption is observed, and
        // a list being retired is exactly the kind of thing the audit should say.
        $field->options()
            ->whereNotIn('value', array_column($options, 'value'))
            ->get()
            ->each
            ->delete();
    }

    /**
     * Carry an option across a `code` change.
     *
     * Without this a rename is a delete plus a create: the audit reads as two
     * unrelated acts, and the option loses its position. A builder update is
     * used deliberately — the group's own rename is already audited, and a
     * second row saying the same thing about its option is noise.
     */
    public function rename(string $from, string $to): void
    {
        $field = $this->field();

        if ($field === null || $from === $to) {
            return;
        }

        FormFieldOption::query()
            ->where('form_field_id', $field->id)
            ->where('value', $from)
            ->update(['value' => $to]);
    }

    /**
     * The picker itself, or null on an install where the form was never seeded.
     *
     * Resolved by slug + key through config rather than by id, because the form
     * is `is_system` — which is what freezes both of those.
     */
    protected function field(): ?FormField
    {
        return FormField::query()
            ->where('key', (string) config('newsletter.fields.groups'))
            ->whereHas('form', fn ($query) => $query->where('slug', (string) config('newsletter.form')))
            ->first();
    }

    /**
     * A group's translated title, per locale, falling back to the internal name.
     *
     * `false` on getTranslation suppresses spatie's own fallback, so a locale
     * that genuinely has no title is absent rather than silently English — the
     * site renderer already falls back to the default locale for a gap.
     *
     * @param  array<int, string>  $codes
     * @return array<string, string>
     */
    protected function label(NewsletterGroup $group, array $codes): array
    {
        $label = [];

        foreach ($codes as $code) {
            $title = (string) $group->getTranslation('title', $code, false);

            if ($title !== '') {
                $label[$code] = $title;
            }
        }

        if ($label !== []) {
            return $label;
        }

        // An untitled list still has to be pickable. The internal name is not
        // prose, so it goes to the default locale only — the same rule
        // FormsSeeder::translate() applies to a bare string.
        return $group->name === null || $group->name === ''
            ? []
            : [Language::defaultCode() => (string) $group->name];
    }
}
