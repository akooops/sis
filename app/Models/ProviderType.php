<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A capability slot (email, sms…). A seeded mirror of config('integrations.types')
 * kept for FK integrity and fast counts; the drivers and their schemas live in
 * code (App\Services\Integrations\IntegrationRegistry).
 */
class ProviderType extends Model
{
    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'code';

    protected $guarded = ['code'];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class, 'provider_type_code', 'code');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
