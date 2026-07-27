<?php

namespace App\Data\Grade;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Position in the array IS the order. */
class ReorderGradesData extends Data
{
    public function __construct(
        /** @var array<int, string> */
        public array $ids,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'distinct', 'exists:grades,id'],
        ];
    }
}
