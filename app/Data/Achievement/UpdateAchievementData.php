<?php

namespace App\Data\Achievement;

use App\Enums\CategoryType;
use App\Models\Language;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateAchievementData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $category_id,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        /** @var array<string, string|null> */
        public array $done_by,
        public string $status,
        public ?string $published_at,
        public string $achieved_at,
        public ?string $css_url,
        public ?string $custom_css,
        public string|Optional|null $thumbnail = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();
        $status = $context->payload['status'] ?? 'draft';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('achievements', 'slug')->ignore(request()->route('achievement')),
            ],

            'category_id' => [
                'required', 'string',
                Rule::exists('categories', 'id')->where('type', CategoryType::Achievements->value),
            ],

            'title' => ['required', 'array:'.implode(',', $codes)],
            'description' => ['required', 'array:'.implode(',', $codes)],
            'content' => ['required', 'array:'.implode(',', $codes)],
            'done_by' => ['sometimes', 'array:'.implode(',', $codes)],

            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'hidden'])],
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            'achieved_at' => ['required', 'date'],

            'css_url' => ['nullable', 'url', 'max:2048'],
            'custom_css' => ['nullable', 'string', 'max:65535'],

            'thumbnail' => ['sometimes', 'nullable', 'string', new CleanUpload('images')],
        ];

        foreach ($codes as $code) {
            $isDefault = $code === $default;

            $rules["title.{$code}"] = $isDefault
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
            $rules["description.{$code}"] = $isDefault
                ? ['required', 'string', 'max:1000']
                : ['nullable', 'string', 'max:1000'];
            $rules["content.{$code}"] = $isDefault
                ? ['required', 'string']
                : ['nullable', 'string'];
            // done_by is optional in every language, including the default: an
            // achievement with no named author is normal.
            $rules["done_by.{$code}"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
