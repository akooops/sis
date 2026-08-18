<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * THE PERSON WHO BOOKS — a parent or guardian, not a booking.
 *
 * The same split as Candidate/JobApplication, and for the same reason: a family
 * touring three stages is one household, and without this table it would be three
 * unrelated rows nobody could tell apart. It is also what makes "has this person
 * already booked this slot?" answerable at all.
 *
 * IDENTITY IS EMAIL OR PHONE, both unique, matched with an OR — see
 * Visitor::scopeIdentifiedBy. Phone is stored E164 (PhoneType::store normalises
 * at submit) so `+966 50 123 4567` and `+966501234567` are one person.
 *
 * Phone is nullable because a future form might not ask for one, and MySQL treats
 * every NULL in a unique index as distinct — which is what we want here (many
 * visitors with no phone must all be allowed) and exactly what makes a nullable
 * column useless for dedup on visit_reservations.
 *
 * Deliberately thin: no address, no notes, no profile. A visitor is a contact,
 * and everything about a particular visit belongs to the reservation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 32)->nullable();

            $table->unique('email');
            $table->unique('phone');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
