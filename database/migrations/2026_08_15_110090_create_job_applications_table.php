<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One act of applying: this person, to this posting, on this day.
 *
 * The candidate holds WHO they are and what they can do; this holds the fact of
 * the application and where it has reached. Score and comment are deliberately
 * NOT here — they live on candidate_matches, so an application shows the same
 * number the recommendation engine produced rather than a second one that drifts.
 *
 * `form_submission_id` is nullOnDelete and everything HR needs is projected out
 * before it could ever go: the raw answers are a useful audit trail, not the
 * record of record.
 *
 * DEDUP LIVES ON THE UNIQUE INDEX, and this is why the general offer is a seeded
 * row rather than a null job_offer_id: MySQL treats every NULL in a unique index
 * as distinct, so a nullable column would silently switch dedup OFF for exactly
 * the spontaneous applications most likely to be duplicated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();

            $table->ulid('job_offer_id');
            $table->foreign('job_offer_id')->references('id')->on('job_offers')->cascadeOnDelete();

            $table->ulid('form_submission_id')->nullable();
            $table->foreign('form_submission_id')->references('id')->on('form_submissions')->nullOnDelete();

            // 'received', not 'new': `new` is a reserved word in PHP and cannot
            // be a state class name, so the stored value matches the class.
            $table->string('status', 32)->default('received');
            $table->dateTime('applied_at')->nullable();

            $table->unique(['job_offer_id', 'candidate_id']);
            $table->index(['job_offer_id', 'status']);
            $table->index(['status', 'applied_at']);
            $table->index('candidate_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
