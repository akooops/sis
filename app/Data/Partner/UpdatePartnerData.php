<?php

namespace App\Data\Partner;

use App\Rules\CleanUpload;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** `order` is deliberately absent — reordering is its own endpoint. */
class UpdatePartnerData extends Data
{
    public function __construct(
        public string $name,
        public string $url,
        public string|Optional|null $logo = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
            'logo' => ['sometimes', 'nullable', 'string', new CleanUpload('images')],
        ];
    }
}
