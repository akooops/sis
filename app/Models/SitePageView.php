<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * One view of one public page.
 *
 * WRITTEN BY App\Http\Middleware\RecordPageView AND BY NOTHING ELSE, through
 * the query builder rather than through Eloquent — see VisitRecorder. That is
 * deliberate: this model is UNOBSERVED and must stay so. AppServiceProvider
 * already documents the rule for bulk machine-written tables and cites Session
 * by name; an observer here would write one activity-log row per page view.
 *
 * READ BY App\Services\Analytics\DashboardMetrics, which never loads a row —
 * every number on the dashboard is computed by MySQL.
 *
 * NOT to be confused with the school-tour booking module. `Visitor`,
 * `VisitReservation`, `VisitSlot` and `visits.*` all belong to that; a Visitor
 * there is a household that booked a campus tour, not somebody reading the site.
 */
class SitePageView extends Model
{
    use HasUlids, MassPrunable;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * `viewed_at` IS the creation time, and the hot path inserts through
     * DB::table(), which maintains no Eloquent timestamps — a created_at column
     * would be a second datetime that only the rare Eloquent write kept in step.
     */
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Both keys are one-way hashes and neither is ever part of an API response:
     * they exist only inside count(distinct …). Hidden for the same reason
     * FormSubmission hides ip_hash and fingerprint.
     */
    protected $hidden = ['visit_key', 'visitor_key'];

    protected $casts = [
        'viewed_at' => 'datetime',
        'is_bot' => 'boolean',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /*
     * None, and deliberately none.
     *
     * A page view is anonymous by construction. It is NOT related to `sessions`
     * either: that table is Prunable and pruned daily, so a foreign key with
     * cascadeOnDelete would quietly delete a year of analytics every night.
     * The session is referenced only as a hash, which no cascade can reach.
     */

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * Rides the daily `model:prune`, which auto-discovers app/Models — there is
     * no bespoke command and nothing to register.
     */
    public function prunable(): Builder
    {
        return static::where('viewed_at', '<', now()->subDays((int) config('analytics.retention_days', 365)));
    }
}
