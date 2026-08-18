<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One offered time, with a limit.
 *
 * CAPACITY COUNTS RESERVATIONS, NOT PEOPLE. A booking takes one seat whatever the
 * size of the party; the party size is recorded on the reservation and shown to
 * whoever runs the tour. That is a deliberate product choice, and it is why
 * visit_services carries max_visitors: the two numbers bound different things.
 *
 * `is_open` is what makes "closed" a real state rather than a euphemism for
 * deleted. A slot with reservations cannot be deleted (VisitSlotsController
 * refuses it); closing it stops new bookings while the existing ones keep their
 * time, which is what a school actually needs when a tour is called off.
 *
 * THE UNIQUE INDEX IS THE BULK GENERATOR'S DEDUP. The old app generated slots in
 * a loop and skipped duplicates by querying for each one in PHP, so two admins
 * generating the same week concurrently both wrote. Here the index decides, and
 * the generator uses insertOrIgnore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_slots', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('visit_service_id');
            $table->foreign('visit_service_id')->references('id')->on('visit_services')->cascadeOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('is_open')->default(true);

            $table->unique(['visit_service_id', 'starts_at', 'ends_at']);
            $table->index(['visit_service_id', 'starts_at']);
            $table->index('starts_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_slots');
    }
};
