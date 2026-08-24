<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One act of booking a venue: this person, for this time.
 *
 * THE PERSON IS A `visitors` ROW, SHARED WITH THE VISITS MODULE. A family that
 * books a school tour in September and the hall in November is one contact
 * record, matched on email OR phone, and the desk can see everything they have
 * booked. That is the whole reason it is not a third near-identical contacts
 * table: the columns would have been the same four either way.
 *
 * DEDUP AND IDEMPOTENCY ARE THE SAME UNIQUE INDEX, (facility_slot_id,
 * visitor_id). It stops one phone or email booking a slot twice, and it is the
 * key ReservationProjector re-runs against, so re-projecting a submission updates
 * the booking instead of making a second one. Neither column is nullable: MySQL
 * treats every NULL in a unique index as distinct, so one nullable column would
 * silently switch dedup off.
 *
 * facility_id is reachable through the slot and is stored anyway, so the bookings
 * list filters by venue with no join. The projector always writes it from the
 * SLOT, never from the submitted answer, so the two cannot drift.
 *
 * The old module had NO status column at all — a booking was created and the only
 * thing anyone could do to it was delete it, which is why its admin screen led
 * with "Contact via WhatsApp" instead of any control over the booking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_reservations', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('facility_slot_id');
            $table->foreign('facility_slot_id')->references('id')->on('facility_slots')->cascadeOnDelete();

            $table->ulid('facility_id');
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();

            $table->ulid('visitor_id');
            $table->foreign('visitor_id')->references('id')->on('visitors')->cascadeOnDelete();

            $table->ulid('form_submission_id')->nullable();
            $table->foreign('form_submission_id')->references('id')->on('form_submissions')->nullOnDelete();

            $table->string('status', 32)->default('pending');
            $table->dateTime('booked_at')->nullable();

            /* The desk's own note. Never shown to the person who booked. */
            $table->text('note')->nullable();

            $table->unique(['facility_slot_id', 'visitor_id'], 'fr_slot_visitor_unique');
            $table->index(['facility_slot_id', 'status']);
            $table->index(['status', 'booked_at']);
            $table->index('facility_id');
            $table->index('visitor_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_reservations');
    }
};
