<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Session as SessionFacade;

class Session extends Model
{
    use HasUlids, Prunable;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $hidden = [
        // The serialized session (CSRF token included).
        'payload',
        // The cookie value itself: whoever holds it holds the session, so it
        // never leaves the server — not in an API response, not in a log.
        'session_id',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /** Whether this row is the session making the current request. */
    public function getIsCurrentAttribute(): bool
    {
        return $this->session_id === SessionFacade::getId();
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * Sessions that have not expired yet.
     *
     * The table is not the truth on its own: Laravel only prunes from gc(), on a
     * `session.lottery` roll (2% of requests), so an expired session can sit here
     * for a long time. Age it out on read as well as pruning on a schedule.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('last_activity', '>=', static::expiredBefore());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('last_activity', '<', static::expiredBefore());
    }

    /**
     * What `model:prune` deletes (scheduled daily in App\Console\Kernel) — an
     * expired session is unusable and no one can log out of it.
     */
    public function prunable(): Builder
    {
        return static::query()->expired();
    }

    /** The moment before which a session is considered dead. */
    public static function expiredBefore(): \Illuminate\Support\Carbon
    {
        return now()->subMinutes((int) config('session.lifetime', 120));
    }

    public function isExpired(): bool
    {
        return $this->last_activity->lt(static::expiredBefore());
    }
}
