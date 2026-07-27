<?php

namespace App\Data\Partner;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** The partner ids in their new display order — position in the array IS the order. */
class ReorderPartnersData extends Data
{
    public function __construct(
        /** @var array<int, string> */
        public array $ids,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'distinct', 'exists:partners,id'],
        ];
    }
}
