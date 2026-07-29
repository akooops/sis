<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
