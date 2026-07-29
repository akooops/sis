<?php

namespace App\Models;

use App\States\Newsletter\NewsletterStatus;
use App\States\NewsletterPublication\NewsletterPublicationStatus;
use App\States\NewsletterPublication\Published;
use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * One issue, with two independent switches: is_published puts it on the website
 * (file + translated title), is_sendable emails it to its groups (subject +
 * content + groups). Both may be on; at least one must be.
 *
 * `name` is the internal label and `subject` the email subject line — both plain,
 * because the body is written once, in whatever language it is written in. Only
 * the public `title` is translated.
 *
 * Two pipelines, never conflated: publish_status + published_at decide when the
 * website archive shows it (newsletters:publish-scheduled), status +
 * scheduled_at/sent_at decide when it is emailed (newsletters:send-scheduled).
 * Neither is ever immediate, and neither reads the other.
 */
class Newsletter extends Model
{
    use HasEnabledTranslations, HasFactory, HasMedia, HasStates, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const FILE_COLLECTION = 'file';

    /**
     * Swapped for the recipient's own link by ShipNewsletter at send time.
     * MIRRORS UNSUBSCRIBE_PLACEHOLDER in resources/js/lib/newsletter.js.
     */
    public const UNSUBSCRIBE_PLACEHOLDER = '{{unsubscribe_url}}';

    /**
     * Swapped for the recipient's own address by ShipNewsletter at send time.
     * MIRRORS EMAIL_PLACEHOLDER in resources/js/lib/newsletter.js.
     */
    public const EMAIL_PLACEHOLDER = '{{email}}';

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title'];

    protected $guarded = ['id'];

    protected $appends = ['file_url'];

    protected $casts = [
        'status' => NewsletterStatus::class,
        'publish_status' => NewsletterPublicationStatus::class,
        'is_published' => 'boolean',
        'is_sendable' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /** The mailer to send through; null falls back to the app default. */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(NewsletterGroup::class, 'newsletter_newsletter_groups');
    }

    public function newsletterGroups(): HasMany
    {
        return $this->hasMany(NewsletterNewsletterGroup::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    public function getFileUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::FILE_COLLECTION);
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Issues the public archive may serve — the switch on, and the pipeline live. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereState('publish_status', Published::class);
    }

    /**
     * One file: attaching a new one frees the old back to the pool.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::FILE_COLLECTION];
    }

    /**
     * Set the targeted groups to exactly these ids. firstOrCreate fires the pivot
     * observer so an attach is audited; removals go through a builder delete.
     */
    public function syncGroups(array $groupIds): void
    {
        $this->newsletterGroups()
            ->whereNotIn('newsletter_group_id', $groupIds)
            ->delete();

        foreach ($groupIds as $groupId) {
            $this->newsletterGroups()->firstOrCreate(['newsletter_group_id' => $groupId]);
        }
    }
}
