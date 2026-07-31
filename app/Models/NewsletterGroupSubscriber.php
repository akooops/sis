<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One public signup on one list. Not a User: these are outside addresses that
 * never sign in, which is why the email lives here and not on a user row.
 *
 * `is_active` is the unsubscribe flag — the row stays, so the address is not
 * silently re-added by the next import.
 */
class NewsletterGroupSubscriber extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'subscribed_at' => 'datetime',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /** Named `group`, so the foreign key has to be spelled out. */
    public function group(): BelongsTo
    {
        return $this->belongsTo(NewsletterGroup::class, 'newsletter_group_id');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * On the model, not in a controller: every entry point must get a signature.
     * The column is unique, so a collision fails the insert loudly rather than
     * letting two people share one unsubscribe link.
     */
    protected static function booted(): void
    {
        static::creating(function (self $subscriber) {
            if (blank($subscriber->signature)) {
                $subscriber->signature = Str::random(64);
            }
        });
    }

    /**
     * The one source for the recipient's link — the job and any mail template read
     * this. Carries the email as well as the signature: both must match the same
     * row, so a leaked or guessed signature is still useless on its own.
     */
    public function unsubscribeUrl(): string
    {
        return route('web.user.newsletter-groups.unsubscribe', [
            'signature' => $this->signature,
            'email' => $this->email,
        ]);
    }
}
