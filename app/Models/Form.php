<?php

namespace App\Models;

use App\States\Form\FormStatus;
use App\States\Form\Published;
use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * A public form: the editorial shape of a Page — slug, publish workflow, own
 * stylesheet — plus everything that makes it collectable.
 *
 * `name` is the internal label; title/description/content/confirmation_message
 * are translated into JSON columns. That is content — the UI catalogue is still
 * lang/*.php.
 *
 * One collection, `thumbnail`. Submission attachments belong to the SUBMISSION,
 * not here, and live on a private disk.
 */
class Form extends Model
{
    use HasEnabledTranslations, HasFactory, HasMedia, HasStates, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const THUMBNAIL_COLLECTION = 'thumbnail';

    /** How a per-visitor cap identifies "the same visitor". */
    public const LIMIT_BY = ['ip', 'fingerprint', 'both'];

    public const CONFIRMATION_TYPES = ['message', 'redirect'];

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title', 'description', 'content', 'confirmation_message'];

    protected $guarded = ['id'];

    protected $appends = ['thumbnail_url'];

    protected $casts = [
        'status' => FormStatus::class,
        'published_at' => 'datetime',
        'is_system' => 'bool',
        'is_limited' => 'bool',
        'is_user_limited' => 'bool',
        'is_spam_filtered' => 'bool',
        'is_captcha_enabled' => 'bool',
        'is_ip_stored' => 'bool',
        'submissions_limit' => 'integer',
        'submissions_count' => 'integer',
        'per_user_limit' => 'integer',
        'min_submit_seconds' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function captchaIntegration(): BelongsTo
    {
        return $this->belongsTo(Integration::class, 'captcha_integration_id');
    }

    public function analyticsIntegration(): BelongsTo
    {
        return $this->belongsTo(Integration::class, 'analytics_integration_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(FormPage::class)->orderBy('order');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('order');
    }

    /** Every option on the form, for building validation without an N+1. */
    public function fieldOptions(): HasManyThrough
    {
        return $this->hasManyThrough(FormFieldOption::class, FormField::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(FormWebhook::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /*
     * READ-ONLY. Do not call attach/detach/sync on either of these.
     *
     * Both pivot tables carry their own ULID primary key with no database
     * default, and attach() writes a raw insert that never runs the model — so
     * it fails outright with "Field 'id' doesn't have a default value". The
     * relations below exist for querying and eager loading.
     *
     * Writes go through the pivot MODELS instead (blockedCountryLinks,
     * notificationGroupLinks), which is also what makes their observers fire so
     * the change is audited against this form.
     */

    public function blockedCountries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'form_blocked_countries');
    }

    public function notificationGroups(): BelongsToMany
    {
        return $this->belongsToMany(NotificationGroup::class, 'form_notification_groups');
    }

    /* The pivot rows themselves — the write path. */

    public function blockedCountryLinks(): HasMany
    {
        return $this->hasMany(FormBlockedCountry::class);
    }

    public function blockedIps(): HasMany
    {
        return $this->hasMany(FormBlockedIp::class);
    }

    public function notificationGroupLinks(): HasMany
    {
        return $this->hasMany(FormNotificationGroup::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /** Null when unset — no bundled fallback. */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::THUMBNAIL_COLLECTION);
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Forms the public site may serve. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereState('status', Published::class);
    }

    /** Live AND still under its total cap. */
    public function scopeAccepting(Builder $query): Builder
    {
        return $query->live()->where(function (Builder $query) {
            $query->where('is_limited', false)
                ->orWhereNull('submissions_limit')
                ->orWhereColumn('submissions_count', '<', 'submissions_limit');
        });
    }

    public function isLive(): bool
    {
        return $this->status instanceof Published;
    }

    /**
     * A seeded form ships with the app: its settings stay editable, but its
     * structure does not. Enforced server-side — hiding the button is not a
     * guard, and the permission gate is off in dev. The enforcement itself lives
     * in UpdateBuilderData::withValidator(), the Data class bound to the builder
     * update route; this only reports the state.
     */
    public function isLocked(): bool
    {
        return (bool) $this->is_system;
    }

    public function hasReachedLimit(): bool
    {
        return $this->is_limited
            && $this->submissions_limit !== null
            && $this->submissions_count >= $this->submissions_limit;
    }

    /** How long a human is assumed to need, with the configured fallback. */
    public function minSubmitSeconds(): int
    {
        return $this->min_submit_seconds ?? (int) config('forms.spam.min_seconds', 5);
    }

    /**
     * Pages in the linear sequence. Interstitials are entered from a button and
     * returned from, so they are not steps and never appear in the progress.
     *
     * @return \Illuminate\Support\Collection<int, FormPage>
     */
    public function linearPages()
    {
        return $this->pages->where('is_interstitial', false)->values();
    }

    /**
     * One thumbnail: a new one frees the old back to the pool.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::THUMBNAIL_COLLECTION];
    }
}
