<?php

namespace App\Data\Partner;

use App\Rules\CleanUpload;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** `order` is deliberately absent — a new partner goes last, and reordering is its own endpoint. */
class StorePartnerData extends Data
{
    public function __construct(
        public string $name,
        public string $url,
        public string $logo,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
            'logo' => ['required', 'string', new CleanUpload('images')],
        ];
    }
}
