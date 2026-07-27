<?php

namespace App\Data\Translation;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateTranslationData extends Data
{
    public function __construct(
        public ?string $value = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            // `present`, not `required`: clearing a translation is a legitimate edit.
            'value' => ['present', 'nullable', 'string', 'max:5000'],
        ];
    }
}
