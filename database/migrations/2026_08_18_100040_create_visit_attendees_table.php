<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One student coming on a visit, projected out of the `students` repeatable group.
 *
 * Typed columns rather than the submission blob, for the same reason
 * candidate_educations exists: "which grades are visiting on Thursday" is a query
 * a JSON answer map cannot serve with an index. The raw answers stay on the
 * FormSubmission; this is what the admissions desk reads.
 *
 * OWNED BY THE RESERVATION, NOT THE VISITOR. A guardian booking the Elementary
 * tour for one child and the High School tour for another is one visitor and two
 * reservations with different attendees; hanging these off the person would merge
 * two unrelated lists.
 *
 * `grade` stores the OPTION VALUE from the form's select (`kg1`, `g7`), never the
 * translated label, so an Arabic booking and an English one are comparable.
 * Nullable because the field is the form's to require, not the schema's.
 *
 * `order` preserves the order the guardian typed the children in — which is
 * usually eldest first, and is in any case theirs to choose.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_attendees', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('visit_reservation_id');
            $table->foreign('visit_reservation_id')->references('id')->on('visit_reservations')->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('grade', 32)->nullable();
            $table->string('current_school')->nullable();
            $table->unsignedInteger('order')->default(0);

            $table->index(['visit_reservation_id', 'order']);
            $table->index('grade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_attendees');
    }
};
