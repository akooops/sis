<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which pools a posting draws from — the other half of the match prefilter.
 *
 * Assigned by cosine against clusters.centroid rather than by clustering the
 * offers separately: offers and candidates share one embedding space, so "which
 * pool is this vacancy nearest?" is already answerable.
 *
 * Same first-class-pivot reasoning as candidate_clusters — never attach()/sync().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_offer_clusters', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('job_offer_id');
            $table->foreign('job_offer_id')->references('id')->on('job_offers')->cascadeOnDelete();

            $table->ulid('cluster_id');
            $table->foreign('cluster_id')->references('id')->on('clusters')->cascadeOnDelete();

            $table->float('distance')->nullable();

            $table->unique(['job_offer_id', 'cluster_id']);
            $table->index('cluster_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_offer_clusters');
    }
};
