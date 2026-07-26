<?php

namespace App\Data\Article;

use App\Models\Language;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Updating an article carries every locale: title/description/content are
 * locale => value maps, which is what the Translations tab submits. Validation
 * errors come back keyed `title.ar`, which useForm flattens straight onto
 * form.errors['title.ar'].
 */
class UpdateArticleData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        public string|Optional|null $thumbnail = null,
        /** @var array<int, string> */
        public array $images = [],
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
                Rule::unique('articles', 'slug')->ignore(request()->route('article')),
            ],

            // `array:en,ar` also rejects unknown keys, so a stale locale left in a
            // cached form can never be written to the JSON column.
            'title' => ['required', 'array:'.implode(',', $codes)],
            'description' => ['required', 'array:'.implode(',', $codes)],
            'content' => ['required', 'array:'.implode(',', $codes)],

            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'hidden'])],
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            'css_url' => ['nullable', 'url', 'max:2048'],
            'custom_css' => ['nullable', 'string', 'max:65535'],

            'thumbnail' => ['sometimes', 'nullable', 'string', new CleanUpload('images')],

            'images' => ['sometimes', 'array'],
            'images.*' => ['string', new CleanUpload('images')],
        ];

        // Written out per locale rather than leaning on `title.*` plus a `title.en`
        // override: the merged ['nullable', 'required', …] rule set that produces
        // is subtle enough to become a future bug.
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
        }

        return $rules;
    }
}
