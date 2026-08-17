<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A language the candidate speaks, and how well.
 *
 * `proficiency` is a plain string validated against Candidate::PROFICIENCIES
 * rather than a database enum, for the reason every other status column here is
 * a string: adding a level to an enum is an ALTER on a live table, and the
 * application already owns the list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_languages', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('proficiency', 16)->nullable();
            $table->unsignedInteger('order')->default(0);

            $table->ulid('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();

            $table->index(['candidate_id', 'order']);
            $table->index(['name', 'proficiency']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_languages');
    }
};
