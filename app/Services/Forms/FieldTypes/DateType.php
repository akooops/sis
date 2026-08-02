<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;
use Carbon\CarbonImmutable;
use Throwable;

/** A date, optionally with a time. */
class DateType extends BaseFieldType
{
    public function code(): string
    {
        return 'date';
    }

    public function label(): string
    {
        return 'Date';
    }

    public function icon(): string
    {
        return 'ki-calendar';
    }

    /**
     * @return array<int, FieldData>
     */
    public function settings(): array
    {
        return [
            new FieldData(key: 'include_time', label: 'Include a time', type: 'switch', default: false, help: 'Renders a date-and-time control and stores the time alongside the date.'),
        ];
    }

    /**
     * @return array<int, FieldData>
     */
    public function validations(): array
    {
        return [
            new FieldData(key: 'min_date', label: 'Earliest', type: 'date'),
            new FieldData(key: 'max_date', label: 'Latest', type: 'date'),
        ];
    }

    public function format(FormField $field): string
    {
        return $this->setting($field, 'include_time') ? 'Y-m-d H:i' : 'Y-m-d';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        $format = $this->format($field);

        $rules = [$this->presence($field), 'date_format:'.$format];

        /*
         * The boundaries are resolved to concrete dates HERE rather than passed
         * through as-is. With a date_format rule present Laravel validates the
         * comparison operand against that same format, so handing it something
         * like `today` produces a rule that can never be satisfied.
         */
        foreach (['min_date' => 'after_or_equal', 'max_date' => 'before_or_equal'] as $key => $operator) {
            $boundary = $this->resolve($this->option($field, $key), $format);

            if ($boundary !== null) {
                $rules[] = "{$operator}:{$boundary}";
            }
        }

        return ['' => $rules];
    }

    /** An admin-entered boundary as a literal date in the field's own format. */
    protected function resolve(mixed $value, string $format): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->format($format);
        } catch (Throwable) {
            // A boundary nobody can parse must not make the field unfillable.
            return null;
        }
    }
}
