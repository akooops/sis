<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One act of booking: this household, for this slot.
 *
 * The visitor holds WHO they are; this holds the fact of the booking and where it
 * has reached. The students coming along are visit_attendees, hanging off THIS
 * row rather than off the visitor — a family brings different children to
 * different tours, and the guardian's contact details are the only thing that
 * carries between them.
 *
 * DEDUP AND IDEMPOTENCY ARE THE SAME INDEX. unique(visit_slot_id, visitor_id) is
 * what stops the same phone or email booking one slot twice — the rule matches a
 * person by either identifier before this index ever sees them — and it is also
 * the key ReservationProjector::project() reruns against, so re-projecting a
 * submission updates the booking instead of making a second one.
 *
 * NEITHER COLUMN IN THAT INDEX IS NULLABLE, for the reason job_applications
 * documents at length: MySQL treats every NULL in a unique index as distinct, so
 * one nullable column silently switches dedup off.
 *
 * `visit_service_id` IS DELIBERATELY REDUNDANT — it is reachable through the slot.
 * It is here so the reservations list filters by service with no join, exactly as
 * job_applications carries both ends of its pair. The projector always writes it
 * from the SLOT, never from the submitted answer, so the two cannot drift.
 *
 * `form_submission_id` is nullOnDelete: the raw answers are a useful audit trail,
 * not the record of record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_reservations', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('visit_slot_id');
            $table->foreign('visit_slot_id')->references('id')->on('visit_slots')->cascadeOnDelete();

            $table->ulid('visit_service_id');
            $table->foreign('visit_service_id')->references('id')->on('visit_services')->cascadeOnDelete();

            $table->ulid('visitor_id');
            $table->foreign('visitor_id')->references('id')->on('visitors')->cascadeOnDelete();

            $table->ulid('form_submission_id')->nullable();
            $table->foreign('form_submission_id')->references('id')->on('form_submissions')->nullOnDelete();

            /* How many people are coming. Does NOT consume capacity — see
               visit_slots — but the tour guide needs the number. */
            $table->unsignedTinyInteger('visitors_count')->default(1);

            $table->string('status', 32)->default('pending');
            $table->dateTime('booked_at')->nullable();

            /* The desk's own note: "called, ringing out", "arriving late".
               Never shown to the visitor. */
            $table->text('note')->nullable();

            $table->unique(['visit_slot_id', 'visitor_id']);
            $table->index(['visit_slot_id', 'status']);
            $table->index(['status', 'booked_at']);
            $table->index('visit_service_id');
            $table->index('visitor_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_reservations');
    }
};
