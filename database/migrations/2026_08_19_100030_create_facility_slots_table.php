<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One offered time on one venue, with a limit. The twin of visit_slots.
 *
 * CAPACITY COUNTS RESERVATIONS. A booking takes one seat; there is no party size
 * on a venue booking, so unlike a visit there is no second number to confuse it
 * with.
 *
 * is_open is what makes "closed" a real state rather than a euphemism for
 * deleted. A slot with reservations cannot be deleted; closing it stops new
 * bookings while the people already booked keep their time.
 *
 * THE UNIQUE INDEX IS THE BULK GENERATOR'S DEDUP. The old facilities module
 * generated slots in a loop and checked for each one with its own query before
 * writing it — N queries, and still racy between two admins generating the same
 * week. Here the database decides and the generator uses insertOrIgnore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_slots', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('facility_id');
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('is_open')->default(true);

            $table->unique(['facility_id', 'starts_at', 'ends_at'], 'fs_facility_period_unique');
            $table->index(['facility_id', 'starts_at']);
            $table->index('starts_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_slots');
    }
};
