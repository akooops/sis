<?php

namespace App\Data\Cluster;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Renaming a pool, and deciding whether the rename survives.
 *
 * THERE IS NO StoreClusterData. Pools are DISCOVERED by the nightly rebuild, not
 * declared — there is no `clusters.store` permission and no store route, because
 * a hand-made pool would have no centroid and so could neither gather members nor
 * place a posting.
 *
 * `is_locked` belongs on this form rather than in a separate toggle: renaming
 * without locking is pointless, since the next rebuild renames it straight back.
 * The controller stays dumb and applies what it is sent; the form defaults the box
 * to checked and says why.
 *
 * `centroid`, `size` and `rebuilt_at` are absent — all three are the rebuild's.
 */
class UpdateClusterData extends Data
{
    public function __construct(
        public string|Optional $name,
        public string|Optional|null $description,
        public bool|Optional $is_locked,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_locked' => ['sometimes', 'boolean'],
        ];
    }
}
