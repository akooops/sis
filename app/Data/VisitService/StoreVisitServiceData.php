<?php

namespace App\Data\VisitService;

use App\Rules\CleanUpload;
use App\Traits\Css\SanitisesCustomCss;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Create takes the default locale only; the rest come from the edit form. */
class StoreVisitServiceData extends Data
{
    use SanitisesCustomCss;

    public function __construct(
        public string $name,
        public string $slug,
        public int $duration_minutes,
        public int $max_visitors,
        public int $order,
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        public string $title,
        public string $description,
        public string $content,
        public string $thumbnail,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $status = $context->payload['status'] ?? 'draft';

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('visit_services', 'slug')],

            // Advertised on the card. A whole working day is the sane ceiling —
            // anything longer is not a tour.
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            // The column is a TINYINT and the counter is a stepper, not a text
            // box: past a couple of dozen this stops being a family visit and
            // becomes a group booking, which is a different thing.
            'max_visitors' => ['required', 'integer', 'min:1', 'max:50'],
            'order' => ['required', 'integer', 'min:0', 'max:65535'],

            // No 'hidden' at birth: nothing public to withdraw yet.
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            // Only a schedule needs a date; publishing is stamped by the controller.
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            'css_url' => ['nullable', 'url:http,https', 'max:2048'],
            // Counted AFTER SanitisesCustomCss has stripped the value, so the
            // limit measures what will be stored.
            'custom_css' => ['nullable', 'string', 'max:65535'],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            // Uncapped: JSON column, real limit is max_allowed_packet. This is the
            // body of the Read-more popup on the public card.
            'content' => ['required', 'string'],

            'thumbnail' => ['required', 'string', new CleanUpload('images')],
        ];
    }
}
