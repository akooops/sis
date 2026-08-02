<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value must sit on a step boundary, counted from a base.
 *
 * Laravel has `multiple_of`, but it counts from zero — a field stepping by 5
 * from a minimum of 2 should accept 7, and multiple_of:5 rejects it. This counts
 * from the field's minimum instead.
 *
 * Compared with a small epsilon because steps are frequently fractional (0.1,
 * 0.05) and binary floats do not land on those exactly.
 */
class StepMultiple implements ValidationRule
{
    public function __construct(
        private float $step,
        private float $base = 0.0,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || $this->step <= 0) {
            return;
        }

        $offset = ((float) $value - $this->base) / $this->step;
        $distance = abs($offset - round($offset));

        if ($distance < 1e-9) {
            return;
        }

        $fail('validation.step')->translate(['step' => $this->step]);
    }
}
