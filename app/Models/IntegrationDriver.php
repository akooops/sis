<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The seeded catalogue of drivers (vendor implementations) — a DB mirror of the
 * classes registered in config('integrations.drivers'). Carries the field schema
 * so the admin UI can list drivers and build forms without booting the classes;
 * the actual send/generate logic still lives in the driver class (resolved by
 * code through App\Services\Integrations\Registry).
 */
class IntegrationDriver extends Model
{
    use HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'schema' => 'array',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function type(): BelongsTo
    {
        return $this->belongsTo(IntegrationType::class, 'integration_type_id');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
