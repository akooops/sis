<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One qualification, projected out of a repeatable group on the application form.
 *
 * TYPED COLUMNS RATHER THAN THE SUBMISSION BLOB, and that is the whole reason the
 * domain half exists: shortlisting means `where education_level = 'bachelor'` and
 * clustering means comparing candidates to each other, neither of which a JSON
 * answer map can do with an index. The raw answers stay on the FormSubmission;
 * this is the projection HR queries.
 *
 * Everything but the institution is nullable — a candidate part-way through a
 * degree has no end year, and a form may not ask for a field of study at all.
 * `order` preserves the sequence the applicant entered rather than sorting by
 * year, because a CV reads in the order its author chose.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_educations', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('institution');
            $table->string('degree')->nullable();
            $table->string('field_of_study')->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);

            $table->ulid('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();

            $table->index(['candidate_id', 'order']);
            $table->index('institution');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_educations');
    }
};
