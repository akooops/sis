<?php

namespace App\Services\Legacy\Importers;

use App\Models\VisitService;
use App\Models\VisitSlot;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * School-tour services and the times they can be booked at.
 *
 * Three small differences, each worth stating.
 *
 * SLUG. The old table had none — a service was addressed by id — and this app's
 * public calendar addresses one by slug, so it is generated from the name and
 * falls back to the legacy id so it can never be empty.
 *
 * STATUS. The old table had none either, and every row it held was offered. They
 * come across PUBLISHED, because importing them as drafts would leave /visits
 * with nothing to pick and read as data loss.
 *
 * CAPACITY VS PARTY SIZE. The old `visit_time_slots.capacity` counted BOOKINGS,
 * which is exactly what `visit_slots.capacity` counts here — so it copies
 * straight across. What the old app had no notion of is `max_visitors`, the cap
 * on how many people ONE booking may bring, so it is seeded from the largest
 * party any existing booking for that service actually had (floor of 1). That is
 * the only number in the old data that means anything like it, and setting it too
 * low would make historical bookings unreproducible.
 */
class VisitServicesImporter extends ContentImporter
{
    public function module(): string
    {
        return 'visits';
    }

    public function describe(): string
    {
        return 'Visit services and time slots';
    }

    public function sources(): array
    {
        return ['visit_services'];
    }

    protected function source(): string
    {
        return 'visit_services';
    }

    protected function target(): string
    {
        return VisitService::class;
    }

    protected function thumbnailCollection(): ?string
    {
        return VisitService::THUMBNAIL_COLLECTION;
    }

    protected function thumbnailIsMainOnly(): bool
    {
        return true;
    }

    protected function hasStatus(): bool
    {
        return false;
    }

    protected function hasOrder(): bool
    {
        return true;
    }

    protected function naturalKey(object $row): ?array
    {
        return ['slug' => $this->slug($row->name ?? null, $row->id)];
    }

    protected function extra(object $row, Model $model): void
    {
        $model->slug = $this->slug($row->name ?? null, $row->id);

        // The legacy column was already in minutes — VisitService::formattedDuration
        // divided it by 60 to print hours.
        $model->duration_minutes = max(1, (int) round((float) ($row->duration ?? 60)));

        $model->max_visitors = $model->exists && $model->max_visitors
            ? $model->max_visitors
            : $this->largestParty((int) $row->id);

        $model->status = $model->exists ? $model->status : 'published';
        $model->published_at ??= $row->created_at ?? now();
    }

    protected function after(object $row, Model $model): void
    {
        $this->slots($row, $model);
    }

    public function run(): void
    {
        parent::run();

        $this->reportOrphanSlots();
    }

    /**
     * Legacy slots that belong to no service at all.
     *
     * The old FK was nullOnDelete, so deleting a visit service left its times
     * behind with a null `visit_service_id`. They are unimportable — this app's
     * `visit_slots.visit_service_id` is NOT NULL, and a bookable time that names
     * no visit is not a thing the calendar can render — but slots() walks
     * per-service and so never sees them, which would make them the one thing
     * the run dropped without saying so.
     */
    protected function reportOrphanSlots(): void
    {
        if (! $this->c->db->has('visit_time_slots')) {
            return;
        }

        $orphans = $this->c->db->table('visit_time_slots')->whereNull('visit_service_id')->count();

        if ($orphans > 0) {
            $this->c->note(
                "{$orphans} legacy time slot(s) belong to no visit service — the old schema nulled the link when a "
                .'service was deleted — and were skipped, because a slot here must name the visit it is for.'
            );
        }
    }

    /** `visit_time_slots` → `visit_slots`. */
    protected function slots(object $row, Model $model): void
    {
        $this->each('visit_time_slots', function (object $slot) use ($model) {
            // Through slot(), which folds a legacy duplicate of the same window
            // onto the row that already holds it — the old table had no unique
            // index and this one does.
            $item = $this->slot(VisitSlot::class, 'visit_time_slots', $slot, 'visit_service_id', $model->id);

            $this->save($item, 'visit_time_slots', (int) $slot->id);
        }, fn ($query) => $query->where('visit_service_id', $row->id));
    }

    /**
     * The biggest party ever booked onto this service, as the new per-booking cap.
     *
     * Floors at 1 and at whatever the old form allowed (five students), so a
     * service with no history still gets a usable number rather than zero, which
     * would make the party-size counter on the public card unusable.
     */
    protected function largestParty(int $legacyServiceId): int
    {
        if (! $this->c->db->has('visit_bookings')) {
            return 5;
        }

        $max = (int) $this->c->db->table('visit_bookings')
            ->where('visit_service_id', $legacyServiceId)
            ->max('visitors_count');

        return max(1, $max ?: 5);
    }
}
