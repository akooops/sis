<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Seeded mirror of the classes in config('integrations.drivers'). Carries the
 * field schema so the UI can build forms without booting the classes; the send
 * logic stays in the driver, resolved through Registry.
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
