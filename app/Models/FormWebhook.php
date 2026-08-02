<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An outbound POST fired after a submission commits.
 *
 * The body is fixed — submission id, form slug, submitted time and the whole
 * answer set under `data`. Parsing that into whatever shape the receiver wants
 * is the receiver's responsibility, so there is nothing here to configure.
 *
 * `auth_config` holds the bearer token / static headers. Like integrations.config
 * it is encrypted AND $hidden AND named in the observer's ignored() AND absent
 * from the read DTO — miss any one of the four and the secret surfaces in an
 * activity diff or an API response.
 */
class FormWebhook extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const AUTH_TYPES = ['none', 'headers', 'bearer'];

    public const METHODS = ['POST', 'PUT', 'PATCH'];

    protected $guarded = ['id'];

    protected $hidden = ['auth_config'];

    protected $casts = [
        'auth_config' => 'encrypted:array',
        'is_enabled' => 'bool',
        'last_delivered_at' => 'datetime',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Whether a secret is stored, without revealing it. */
    public function hasAuthSecret(): bool
    {
        return filled($this->auth_config);
    }

    /**
     * Stamp WHEN this webhook last fired — the outcome is not kept on the row.
     * The response code and the error live in the `integrations` log, which is
     * where anyone diagnosing a dead endpoint is reading anyway; a column would
     * be a second, staler copy of it.
     *
     * Called for an attempt that got a response AND for one that finally gave
     * up, so the column answers "has anything gone out, and when" rather than
     * "did it work".
     *
     * saveQuietly on purpose — this moves on every delivery, and an audit row
     * each would bury the changes an admin actually made.
     */
    public function recordDelivery(): void
    {
        $this->last_delivered_at = now();

        $this->saveQuietly();
    }
}
