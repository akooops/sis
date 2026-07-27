<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A capability slot (email, sms, ai…), mirroring config('integrations.types').
 */
class IntegrationType extends Model
{
    use HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(IntegrationDriver::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
