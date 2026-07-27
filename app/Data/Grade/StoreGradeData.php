<?php

namespace App\Data\Grade;

use App\Models\Grade;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Create takes the default locale only; the rest come from the edit form. */
class StoreGradeData extends Data
{
    public function __construct(
        public string $program_id,
        public string $name,
        public string $title,
        /** @var array<int, string> */
        public array $guidelines = [],
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'program_id' => ['required', 'string', Rule::exists('programs', 'id')],

            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],

            // Downloadables, in submitted order.
            'guidelines' => ['sometimes', 'array'],
            'guidelines.*' => ['string', new CleanUpload(Grade::GUIDELINE_TYPES)],
        ];
    }
}
