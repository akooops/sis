<?php

namespace App\Data\Form;

use App\Models\FormSubmission;
use App\Models\Media;
use Illuminate\Support\Facades\URL;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * One submission, as the admin reads it.
 *
 * `id` is the whole identity — the ULID is the reference the visitor was shown,
 * so there is no second code to carry.
 *
 * `data` and `fields` are handed over as-is: the answers keyed by field key,
 * plus the {key: {label, type}} snapshot taken at submit time. Both are written
 * in the form's reading order and both name every capturing field, so the UI can
 * render them by iterating either one. The pair is what keeps an old submission
 * readable after its form was relabelled, so the UI renders from the snapshot
 * and never re-reads the live form.
 *
 * THE ADDRESS NEVER LEAVES THE SERVER. ip_address / ip_hash / fingerprint are
 * $hidden on the model, and they are absent here too — a DTO that named them
 * would quietly undo that, because $hidden only guards Eloquent's own
 * serialisation. `has_ip` is the whole of what the UI needs: whether the form
 * was storing addresses when this one came in.
 *
 * `files` carries SIGNED, short-lived links. Answer uploads live on the private
 * disk, so Media::url is null for them by design; the only way to a file is
 * api.v1.admin.form-submissions.file, which is signature- and permission-gated.
 */
class FormSubmissionData extends Data
{
    public function __construct(
        public string $id,
        public string $form_id,
        public ?string $form_name,
        public string $status,

        public ?string $submitted_at,
        public ?int $duration_seconds,

        public ?string $country_code,
        public ?string $city,
        public ?string $device_type,
        public ?string $browser,
        public ?string $os,
        public bool $has_ip,

        public int $spam_score,
        /** @var array<int, mixed>|null */
        public ?array $spam_reasons,
        public bool $is_honeypot_triggered,
        /**
         * A LIST of the field keys that failed — recordFailure() stores
         * array_keys($validator->errors()->toArray()), not the messages. The
         * keyed shape is typed too, because the column is free-form JSON.
         *
         * @var array<int, string>|array<string, mixed>|null
         */
        public ?array $validation_errors,

        public ?string $referrer,
        /** @var array<string, mixed>|null */
        public ?array $utm,
        public ?string $page_url,

        /** @var array<string, mixed> */
        public array $data,
        /** @var array<string, mixed> */
        public array $fields,
        /** @var array<int, array<string, mixed>> */
        public array $files,

        public Lazy|FormData|null $form,

        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(FormSubmission $submission): self
    {
        return new self(
            id: $submission->id,
            form_id: $submission->form_id,
            form_name: $submission->relationLoaded('form') ? $submission->form?->name : null,
            status: (string) $submission->status,

            submitted_at: $submission->submitted_at?->toIso8601String(),
            duration_seconds: $submission->duration_seconds,

            country_code: $submission->country_code,
            city: $submission->city,
            device_type: $submission->device_type,
            browser: $submission->browser,
            os: $submission->os,
            // The flag, never the value — see the class docblock.
            has_ip: $submission->ip_address !== null,

            spam_score: (int) $submission->spam_score,
            spam_reasons: $submission->spam_reasons,
            is_honeypot_triggered: (bool) $submission->is_honeypot_triggered,
            validation_errors: $submission->validation_errors,

            referrer: $submission->referrer,
            utm: $submission->utm,
            page_url: $submission->page_url,

            data: $submission->data ?? [],
            fields: $submission->fields ?? [],
            files: static::files($submission),

            form: Lazy::whenLoaded('form', $submission, fn () => FormData::from($submission->form)),

            created_at: $submission->created_at?->toIso8601String(),
            updated_at: $submission->updated_at?->toIso8601String(),
        );
    }

    /**
     * The answer uploads, each with a signed link.
     *
     * Only ever built from an ALREADY LOADED media relation. Reaching for it
     * here would be one query per row on the index, and the list does not show
     * files anyway — show() loads it, index() does not.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function files(FormSubmission $submission): array
    {
        if (! $submission->relationLoaded('media')) {
            return [];
        }

        $keys = static::fieldKeysByMedia($submission);
        $minutes = (int) config('forms.submissions.file_link_ttl', 30);

        return $submission->getMedia(FormSubmission::ANSWERS_COLLECTION)
            ->map(fn (Media $media) => [
                'id' => $media->id,
                'field_key' => $keys[$media->id] ?? null,
                'name' => $media->name,
                'mime' => $media->mime_type,
                'size' => (int) $media->size,
                'url' => URL::temporarySignedRoute(
                    'api.v1.admin.form-submissions.file',
                    now()->addMinutes(max(1, $minutes)),
                    ['formSubmission' => $submission->id, 'media' => $media->id],
                ),
            ])
            ->values()
            ->all();
    }

    /**
     * media id => the field key that answer belongs to.
     *
     * The answer holds the id of the media the submission ENDED UP owning
     * (attach() copies an already-owned file and the copy's id is written back),
     * so this map is exact rather than a guess.
     *
     * A repeatable group's answer is a list of OBJECTS, so a file inside one sits
     * a level deeper than every other answer. It is keyed `group.child` rather
     * than by the child alone, because the drawer showing "certificate" three
     * times over says nothing about which entry each belongs to.
     *
     * @return array<string, string>
     */
    protected static function fieldKeysByMedia(FormSubmission $submission): array
    {
        $keys = [];

        foreach ($submission->data ?? [] as $key => $value) {
            foreach (is_array($value) ? $value : [$value] as $item) {
                if (is_string($item) && $item !== '') {
                    $keys[$item] = (string) $key;

                    continue;
                }

                // A group instance: {child key => answer}.
                if (! is_array($item)) {
                    continue;
                }

                foreach ($item as $childKey => $childValue) {
                    foreach (is_array($childValue) ? $childValue : [$childValue] as $id) {
                        if (is_string($id) && $id !== '') {
                            $keys[$id] = $key.'.'.$childKey;
                        }
                    }
                }
            }
        }

        return $keys;
    }
}
