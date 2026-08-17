<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One job the candidate has held, projected out of the application form's
 * experience group.
 *
 * `is_current` rather than an absent end year: "still there" and "did not say"
 * are different answers, and only the first should count toward years of
 * experience. The renderer's checkbox clears end_year, so the pair stays
 * consistent, but nothing here depends on that — a row with both is read as
 * current.
 *
 * EMPLOYMENT GAPS ARE A REAL SIGNAL in this market — international schools in
 * Riyadh check them explicitly — so the years are typed columns the scorer can
 * actually reason over rather than prose inside a description.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_experiences', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('company_name');
            $table->string('job_title')->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);

            $table->ulid('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();

            $table->index(['candidate_id', 'order']);
            $table->index('job_title');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_experiences');
    }
};
