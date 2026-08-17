<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A talent pool, DISCOVERED rather than declared.
 *
 * Nobody knows the categories up front, so these are not seeded: a nightly job
 * clusters candidate embeddings (k-means in PHP — MySQL has no vector type) and
 * an LLM names each result, so HR reads "Primary Maths & Science" instead of
 * "Cluster 7".
 *
 * `centroid` is what makes one shared space work: a job offer is assigned to its
 * nearest cluster by cosine against these, so offers need no clustering pass of
 * their own.
 *
 * WHY CLUSTERS EXIST AT ALL: they bound the match matrix. Scoring every candidate
 * against every open posting is 500 × 30 = 15,000 LLM calls; scoring only within
 * a shared cluster keeps it linear and affordable.
 *
 * `is_locked` stops the next rebuild renaming a cluster an admin has titled by
 * hand. Identity is carried across rebuilds by matching new centroids to the
 * nearest previous one — without that, ids shuffle nightly and every saved
 * filter silently points somewhere else.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clusters', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->text('description')->nullable();

            $table->json('centroid')->nullable();
            $table->unsignedInteger('size')->default(0);

            $table->boolean('is_locked')->default(false);
            $table->dateTime('rebuilt_at')->nullable();

            $table->index('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clusters');
    }
};
