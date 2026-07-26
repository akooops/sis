<?php

namespace App\Data\Language;

use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateLanguageData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public bool $is_default = false,
        public bool $is_rtl = false,
        public bool $is_enabled = false,
        public string|Optional|null $flag = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Changing the code renames lang/{code}/ — see LanguageObserver.
            'code' => ['required', 'string', 'max:10', 'regex:'.config('translations.code_pattern'), Rule::unique('languages', 'code')->ignore(request()->route('language'))],

            'is_default' => ['sometimes', 'boolean'],
            'is_rtl' => ['sometimes', 'boolean'],
            'is_enabled' => ['sometimes', 'boolean'],

            'flag' => ['sometimes', 'nullable', 'string', new CleanUpload('images')],
        ];
    }
}
