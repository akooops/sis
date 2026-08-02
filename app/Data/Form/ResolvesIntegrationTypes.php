<?php

namespace App\Data\Form;

use App\Models\IntegrationType;

/**
 * Shared by the create and update payloads, which pin the same two integration
 * slots and must scope them the same way — a captcha slot that accepted an
 * analytics account on create and refused it on update would be a bug you only
 * meet on a live public form.
 */
trait ResolvesIntegrationTypes
{
    /**
     * Integration ids of a given type code. A missing type yields an empty set,
     * so the exists rule fails closed rather than matching everything.
     *
     * @return array<int, string>
     */
    protected static function typeIds(string $typeCode): array
    {
        return IntegrationType::query()->where('code', $typeCode)->pluck('id')->all();
    }
}
