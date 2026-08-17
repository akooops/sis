<?php

namespace App\Contracts\Forms;

use App\Data\Integration\FieldData;
use App\Models\FormField;

/**
 * One kind of element a form can hold.
 *
 * Deliberately shaped like App\Contracts\Integrations\Driver, and it returns the
 * SAME FieldData DTO, because that is what resources/admin/js/components/form/
 * SchemaField.svelte already renders. A forked settings DTO would fork the
 * renderer too, and adding an element would stop being a backend-only change.
 *
 * Adding an element is one class plus one line in config('forms.field_types').
 * The catalogue is never mirrored into a table: a mirror drifts the moment
 * someone deploys without reseeding, and the builder would then offer settings
 * the submit-time compiler cannot read.
 */
interface FieldType
{
    /** Stable machine code stored in form_fields.type. */
    public function code(): string;

    public function label(): string;

    /** Keenicon class, for the builder palette. */
    public function icon(): string;

    /** Palette grouping: input | choice | display | action. */
    public function group(): string;

    /** Whether this element captures an answer. Headings and buttons do not. */
    public function isInput(): bool;

    /** Whether it owns form_field_options rows. */
    public function hasOptions(): bool;

    /**
     * Whether it owns CHILD FIELDS — only a repeatable group does.
     *
     * A group is the one element whose answer is a list of objects rather than a
     * scalar or a list of scalars, so it is also the one element that recurses:
     * it compiles its children's rules under its own key, and stores their values
     * inside its own array. Everything downstream reads that as a single answer.
     */
    public function hasChildren(): bool;

    /**
     * Translatable attributes this element actually uses, so the builder shows
     * only the boxes that mean something for it.
     *
     * @return array<int, string>
     */
    public function translatable(): array;

    /**
     * Editable settings, stored in form_fields.settings.
     *
     * @return array<int, FieldData>
     */
    public function settings(): array;

    /**
     * Editable validation options, stored in form_fields.validation.
     *
     * @return array<int, FieldData>
     */
    public function validations(): array;

    /**
     * Laravel rules for one submitted answer, as a KEYED map rather than a flat
     * list: '' is the rule set for `fields.<key>` itself and '.*' the set for
     * each member of an array answer.
     *
     * The '.*' half is load-bearing. Without it a multi-select accepts arbitrary
     * strings that were never options, and they land in the stored answer and in
     * the CSV export.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array;

    /** Normalise a submitted value on its way into form_submissions.data. */
    public function store(FormField $field, mixed $value): mixed;

    /** Render a stored answer for a table cell or a CSV column. */
    public function display(FormField $field, mixed $value, ?string $locale = null): string;
}
