<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Form\BuilderData;
use App\Data\Form\UpdateBuilderData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The builder's own endpoint: the whole tree in, the whole tree out.
 *
 * ONE ATOMIC SAVE, not auto-PATCH per drag. Three reasons, all of them real:
 * observers write an audit row per model write, so autosave would log every
 * fumbled drag; one cross-page move rewrites N rows' page and order, which is
 * not atomic as separate requests; and a form is a public artifact that must
 * never be live in a half-built state.
 */
class FormBuilderController extends ApiController
{
    /** Translatable attributes per model, so writes go through mergeTranslations. */
    protected const PAGE_TRANSLATABLE = ['title'];

    protected const FIELD_TRANSLATABLE = ['label', 'placeholder', 'value', 'content'];

    public function show(Form $form): JsonResponse
    {
        $form->load(['pages.fields.options']);

        return $this->respond(BuilderData::from($form), 'Form builder loaded successfully');
    }

    public function update(UpdateBuilderData $data, Form $form): JsonResponse
    {
        // The is_system structure lock is enforced in UpdateBuilderData, so a
        // locked form never reaches this method.
        $this->assertConsistent($data, $form);

        DB::transaction(function () use ($data, $form) {
            $keptPages = [];
            $keptFields = [];
            $keptOptions = [];

            /*
             * TWO PASSES, and the order is load-bearing. A button on page 1 can
             * target page 2, so every page row has to exist before any field is
             * written — interleaving them trips ff_target_page_fk on a form
             * whose first page links forward.
             */
            foreach ($data->pages as $pageIndex => $incomingPage) {
                $page = $form->pages()->firstWhere('id', $incomingPage['id'])
                    ?? tap(new FormPage, fn (FormPage $p) => $p->id = $incomingPage['id']);

                $page->form_id = $form->id;
                $page->name = $incomingPage['name'];
                $page->order = $pageIndex;
                $page->is_interstitial = (bool) ($incomingPage['is_interstitial'] ?? false);
                $page->css_id = $incomingPage['css_id'] ?? null;
                $page->css_class = $incomingPage['css_class'] ?? null;

                $this->applyTranslations($page, $incomingPage, self::PAGE_TRANSLATABLE);
                $page->save();

                $keptPages[] = $page->id;
            }

            // Second pass: fields and their options, now that every target exists.
            foreach ($data->pages as $incomingPage) {
                $pageId = $incomingPage['id'];

                foreach ($incomingPage['fields'] ?? [] as $fieldIndex => $incomingField) {
                    $field = FormField::where('form_id', $form->id)->firstWhere('id', $incomingField['id'])
                        ?? tap(new FormField, fn (FormField $f) => $f->id = $incomingField['id']);

                    $field->form_id = $form->id;
                    $field->form_page_id = $pageId;
                    $field->type = $incomingField['type'];
                    $field->key = $incomingField['key'];
                    $field->order = $fieldIndex;
                    $field->is_required = (bool) ($incomingField['is_required'] ?? false);
                    $field->is_unique = (bool) ($incomingField['is_unique'] ?? false);
                    $field->settings = $incomingField['settings'] ?? null;
                    $field->validation = $incomingField['validation'] ?? null;
                    $field->target_form_page_id = $incomingField['target_form_page_id'] ?? null;
                    $field->css_id = $incomingField['css_id'] ?? null;
                    $field->css_class = $incomingField['css_class'] ?? null;

                    $this->applyTranslations($field, $incomingField, self::FIELD_TRANSLATABLE);
                    $field->save();

                    $keptFields[] = $field->id;

                    foreach ($incomingField['options'] ?? [] as $optionIndex => $incomingOption) {
                        $option = FormFieldOption::where('form_field_id', $field->id)->firstWhere('id', $incomingOption['id'])
                            ?? tap(new FormFieldOption, fn (FormFieldOption $o) => $o->id = $incomingOption['id']);

                        $option->form_field_id = $field->id;
                        $option->value = $incomingOption['value'];
                        $option->order = $optionIndex;
                        $option->is_default = (bool) ($incomingOption['is_default'] ?? false);

                        $this->applyTranslations($option, $incomingOption, ['label']);
                        $option->save();

                        $keptOptions[] = $option->id;
                    }
                }
            }

            /*
             * Delete what the payload dropped, innermost first. A page delete
             * would cascade its fields at the database level, which fires no
             * model events — so the audit log would lose them. Deleting
             * explicitly keeps the trail and frees any attached media.
             */
            FormFieldOption::whereIn('form_field_id', $keptFields)
                ->whereNotIn('id', $keptOptions ?: ['-'])
                ->get()
                ->each
                ->delete();

            FormField::where('form_id', $form->id)
                ->whereNotIn('id', $keptFields ?: ['-'])
                ->get()
                ->each
                ->delete();

            FormPage::where('form_id', $form->id)
                ->whereNotIn('id', $keptPages ?: ['-'])
                ->get()
                ->each
                ->delete();
        });

        $form->load(['pages.fields.options']);

        return $this->respond(BuilderData::from($form), 'Form builder saved successfully');
    }

    /**
     * Cross-cutting checks the field-level rules cannot express, because each
     * one needs to see the whole payload or the database.
     */
    protected function assertConsistent(UpdateBuilderData $data, Form $form): void
    {
        $errors = [];

        $pageIds = array_column($data->pages, 'id');

        if (count($pageIds) !== count(array_unique($pageIds))) {
            $errors['pages'] = 'Two pages were sent with the same id.';
        }

        $keys = [];
        $fieldIds = [];

        foreach ($data->pages as $pageIndex => $page) {
            foreach ($page['fields'] ?? [] as $fieldIndex => $field) {
                $path = "pages.{$pageIndex}.fields.{$fieldIndex}";
                $key = $field['key'] ?? null;

                if (isset($keys[$key])) {
                    $errors["{$path}.key"] = "The key \"{$key}\" is used more than once. Keys are the answer's name, so they have to be unique within a form.";
                }

                $keys[$key] = true;
                $fieldIds[] = $field['id'];

                // A button may only target a page that exists in this payload —
                // otherwise it could point at a page the same save deletes.
                $target = $field['target_form_page_id'] ?? null;

                if ($target !== null && ! in_array($target, $pageIds, true)) {
                    $errors["{$path}.target_form_page_id"] = 'That button points at a page which is not part of this form.';
                }

                $options = array_column($field['options'] ?? [], 'value');

                if (count($options) !== count(array_unique($options))) {
                    $errors["{$path}.options"] = 'Two options share the same value.';
                }
            }
        }

        if (count($fieldIds) !== count(array_unique($fieldIds))) {
            $errors['pages'] = 'Two fields were sent with the same id.';
        }

        // Renaming a key orphans every answer already stored under the old one,
        // plus the export column and any webhook mapping that names it.
        $existing = FormField::where('form_id', $form->id)->pluck('key', 'id');

        if ($existing->isNotEmpty() && $form->submissions()->exists()) {
            foreach ($data->pages as $pageIndex => $page) {
                foreach ($page['fields'] ?? [] as $fieldIndex => $field) {
                    $old = $existing[$field['id']] ?? null;

                    if ($old !== null && $old !== ($field['key'] ?? null)) {
                        $errors["pages.{$pageIndex}.fields.{$fieldIndex}.key"] =
                            "This form already has submissions, so \"{$old}\" cannot be renamed — the stored answers are filed under it.";
                    }
                }
            }

            // Removing a field that people have already answered destroys the
            // only copy of those answers. There is no override: an answered
            // field stays until its submissions do.
            foreach ($existing as $id => $key) {
                if (in_array($id, $fieldIds, true)) {
                    continue;
                }

                if ($this->hasAnswers($form, $key)) {
                    $errors['pages'] = "\"{$key}\" cannot be removed — answers have already been given for it. Export the submissions first, then delete them if you want the field gone.";
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** Whether any submission carries an answer for this key. */
    protected function hasAnswers(Form $form, string $key): bool
    {
        return $form->submissions()->whereNotNull("data->{$key}")->exists();
    }

    /**
     * Translatable writes go through mergeTranslations on an EXISTING row, so a
     * builder that only rendered the enabled locales cannot drop the others.
     *
     * @param  array<string, mixed>  $incoming
     * @param  array<int, string>  $keys
     */
    protected function applyTranslations($model, array $incoming, array $keys): void
    {
        $attributes = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $incoming) && is_array($incoming[$key])) {
                $attributes[$key] = $incoming[$key];
            }
        }

        if ($attributes === []) {
            return;
        }

        $model->fill($model->exists ? $model->mergeTranslations($attributes) : $attributes);
    }
}
