<?php

namespace App\Models;

use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One submission — the answers AND everything measured about them.
 *
 * THE ULID `id` IS THE REFERENCE. It is what the thank-you page quotes, what the
 * webhook payload carries and what the admin searches on — there is no second
 * human-friendly code to allocate, keep unique and keep in step with it.
 *
 * Answers live in `data`, keyed by the field's machine key and written in the
 * form's own reading order (page order, then field order within the page) with
 * an explicit null for every capturing field the visitor left blank, so the map
 * is a complete picture of the form rather than only what came back. `fields`
 * holds a {key: {label, type}} snapshot taken at submit time, in that same
 * order. Nothing here re-reads the live form, so a two-year-old submission stays
 * readable after its form was relabelled or had fields removed.
 *
 * `status` is a plain string rather than a spatie state machine: the server
 * writes it once per outcome, no admin transitions it, a TransitionNotFound on
 * the public path would 500 the form, and the abandonment sweep is a bulk
 * builder update that a state machine forbids.
 *
 * Prunable, NOT MassPrunable. MassPrunable::pruneAll() issues a builder delete
 * that fires no model events, so the observer never frees the attached media —
 * the rows would keep pointing at a deleted submission and MediaController
 * refuses to delete an owned media, leaving files that nothing can remove.
 */
class FormSubmission extends Model
{
    use HasFactory, HasMedia, HasUlids, Prunable;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /** File answers. One collection, not one per field. */
    public const ANSWERS_COLLECTION = 'answers';

    /**
     * started           : a draft, created by the first telemetry beacon.
     * completed         : accepted and stored.
     * abandoned         : started, never submitted, swept by forms:close-abandoned.
     * spam              : rejected by the honeypot, min-time or a forged token.
     * validation_failed : reached validation and did not pass.
     */
    public const STATUSES = ['started', 'completed', 'abandoned', 'spam', 'validation_failed'];

    protected $guarded = ['id'];

    /** The raw address is readable data; it is never part of an API response. */
    protected $hidden = ['ip_address', 'ip_hash', 'fingerprint'];

    protected $casts = [
        'data' => 'array',
        'fields' => 'array',
        'spam_reasons' => 'array',
        'validation_errors' => 'array',
        'focus_order' => 'array',
        'utm' => 'array',
        'steps' => 'array',
        'is_honeypot_triggered' => 'bool',
        'loaded_at' => 'datetime',
        'submitted_at' => 'datetime',
        'spam_score' => 'integer',
        'duration_seconds' => 'integer',
        'ttfi_ms' => 'integer',
        'fill_ms' => 'integer',
        'time_on_page_ms' => 'integer',
        'validation_error_count' => 'integer',
        'submit_attempts' => 'integer',
        'back_navigations' => 'integer',
        'scroll_depth_percent' => 'integer',
        'click_count' => 'integer',
        'paste_count' => 'integer',
        'pages_completed' => 'integer',
        'timezone_offset_minutes' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function lastPage(): BelongsTo
    {
        return $this->belongsTo(FormPage::class, 'last_form_page_id');
    }

    public function abandonedField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'abandoned_form_field_id');
    }

    public function fieldEvents(): HasMany
    {
        return $this->hasMany(FormSubmissionFieldEvent::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /** The answer to one field, by machine key. */
    public function answer(string $key): mixed
    {
        return data_get($this->data, $key);
    }

    /** The label this field carried when the submission was made. */
    public function labelFor(string $key): ?string
    {
        return data_get($this->fields, "{$key}.label");
    }

    /**
     * Drafts idle past a day, and anything unfinished past the retention
     * window. A COMPLETED submission is never pruned — it is the record the
     * form exists to collect.
     */
    public function prunable(): Builder
    {
        $days = (int) config('forms.submissions.prune_after_days', 90);
        $hours = (int) config('forms.submissions.prune_started_after_hours', 24);

        return static::query()
            ->where('status', '!=', 'completed')
            ->where(function (Builder $query) use ($days, $hours) {
                $query->where(function (Builder $q) use ($hours) {
                    $q->where('status', 'started')->where('updated_at', '<', now()->subHours($hours));
                })->orWhere('created_at', '<', now()->subDays($days));
            });
    }

    /**
     * `answers` holds every file on the form, so it is multi-file: declaring it
     * single would make each upload free the one before it.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [];
    }
}
