<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How well one candidate fits one posting — ONE SCORING TABLE, NOT TWO.
 *
 * This covers both halves of what HR asked for, because they are the same
 * question asked from opposite ends: a candidate's page reads it filtered by
 * candidate ("other postings this person suits"), a posting's drawer reads it
 * filtered by job_offer ("good people we already have"). An application is 1:1
 * with its own row here, so the score shown on an application IS its match —
 * there is no second scorer to drift from this one, and "applied" versus
 * "recommended" is simply whether a job_applications row exists.
 *
 * `score` is 0-100 and nullable: null means not yet scored, which is different
 * from scored badly, and the queue writes it after the applicant has already
 * been thanked.
 *
 * `comment` is the model's reasoning in plain language. It exists so a
 * shortlisting decision can be explained to the person who has to defend it,
 * which a bare number cannot do.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_matches', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();

            $table->ulid('job_offer_id');
            $table->foreign('job_offer_id')->references('id')->on('job_offers')->cascadeOnDelete();

            $table->unsignedTinyInteger('score')->nullable();
            $table->text('comment')->nullable();
            $table->dateTime('matched_at')->nullable();

            $table->unique(['candidate_id', 'job_offer_id']);
            // The two rankings the admin actually renders.
            $table->index(['job_offer_id', 'score']);
            $table->index(['candidate_id', 'score']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_matches');
    }
};
