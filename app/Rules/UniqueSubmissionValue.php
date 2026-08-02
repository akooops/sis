<?php

namespace App\Rules;

use App\Models\Form;
use App\Models\FormField;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * "Only one person may give this answer" — one email per competition entry, one
 * membership number per registration.
 *
 * Counts COMPLETED submissions only. An abandoned draft holding an address would
 * otherwise lock that address out permanently, and the person who abandoned it
 * is exactly the person most likely to come back and try again.
 *
 * Answers live in a JSON column, so this is a path lookup rather than an indexed
 * column. That is fine at the volume a form runs at; if one ever gets large, the
 * fix is a generated column plus an index on that single path.
 */
class UniqueSubmissionValue implements ValidationRule
{
    public function __construct(
        private Form $form,
        private FormField $field,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '' || is_array($value)) {
            return;
        }

        $taken = $this->form->submissions()
            ->completed()
            ->where("data->{$this->field->key}", $value)
            ->exists();

        if ($taken) {
            $fail('validation.unique')->translate();
        }
    }
}
