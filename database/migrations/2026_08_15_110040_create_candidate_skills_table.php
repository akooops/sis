<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One skill, one row.
 *
 * A TABLE RATHER THAN A JSON COLUMN, because "show me everyone who lists IB" has
 * to be a `whereHas` with an index behind it, and that is the single most
 * obvious thing HR will ask of this data. A JSON array would make every skill
 * filter a full scan.
 *
 * `name` is stored as the candidate typed it and `fold` is the lowercased form
 * the unique index and every lookup use — so "IB" and "ib" are one skill for
 * matching while the record still shows what they wrote.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_skills', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('fold');

            $table->ulid('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();

            $table->unique(['candidate_id', 'fold']);
            $table->index('fold');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_skills');
    }
};
