<?php

namespace App\Models;

use App\States\Media\MediaScanState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Spatie\ModelStates\HasStates;

/**
 * App-specific media model:
 *  - ULID primary key (HasUlids), matching the rest of the schema.
 *  - `state` scan status (HasStates) so an unattached upload can be tracked
 *    through pending -> clean/infected without a separate table.
 */
class Media extends BaseMedia
{
    use HasStates, HasUlids;

    public function __construct(array $attributes = [])
    {
        // Merge (not replace) so Spatie's JSON casts are preserved.
        $this->mergeCasts(['state' => MediaScanState::class]);

        parent::__construct($attributes);
    }
}
