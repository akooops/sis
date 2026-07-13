<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * App activity model so activity_log rows use a ULID primary key like the rest
 * of the schema. Registered via config/activitylog.php -> activity_model.
 */
class Activity extends SpatieActivity
{
    use HasUlids;
}
