<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;
use App\Rules\StepMultiple;

/** A numeric answer. */
class NumberType extends BaseFieldType
{
    public function code(): string
    {
        return 'number';
    }

    public function label(): string
    {
        return 'Number';
    }

    public function icon(): string
    {
        return 'ki-abstract-25';
    }

    /**
     * @return array<int, FieldData>
     */
    public function settings(): array
    {
        return [
            new FieldData(key: 'step', label: 'Step', type: 'number', default: 1, help: 'The increment the control moves in. Also enforced on submit.'),
        ];
    }

    /**
     * @return array<int, FieldData>
     */
    public function validations(): array
    {
        return [
            new FieldData(key: 'min', label: 'Minimum', type: 'number'),
            new FieldData(key: 'max', label: 'Maximum', type: 'number'),
            new FieldData(key: 'integer_only', label: 'Whole numbers only', type: 'switch', default: false),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        $rules = [$this->presence($field)];

        $rules[] = $this->option($field, 'integer_only') ? 'integer' : 'numeric';

        if (($min = $this->option($field, 'min')) !== null) {
            $rules[] = 'min:'.$min;
        }

        if (($max = $this->option($field, 'max')) !== null) {
            $rules[] = 'max:'.$max;
        }

        // A step of 1 is already covered by `integer`, and a step on a field with
        // no minimum has no anchor to count from — so only add the rule when it
        // actually constrains something.
        $step = $this->setting($field, 'step');

        if ($step !== null && (float) $step > 0 && (float) $step !== 1.0) {
            $rules[] = new StepMultiple((float) $step, $this->option($field, 'min') !== null ? (float) $this->option($field, 'min') : 0.0);
        }

        return ['' => $rules];
    }

    public function store(FormField $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->option($field, 'integer_only') ? (int) $value : (float) $value;
    }
}
