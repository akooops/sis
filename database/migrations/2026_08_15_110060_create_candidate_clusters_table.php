<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which pools a candidate belongs to.
 *
 * A FIRST-CLASS PIVOT with its own ULID, like role_permission and
 * api_key_permission: attach()/sync() write a raw insert that never runs the
 * model, so a ULID primary key with no database default fails outright with
 * "Field 'id' doesn't have a default value". Writes go through the model, which
 * is also what makes its observer fire so the change is audited against the
 * candidate.
 *
 * `distance` is the cosine distance to the centroid — kept so the admin can sort
 * a pool by how central a member is, and so a rebuild can tell a core member from
 * one that barely qualified.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_clusters', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();

            $table->ulid('cluster_id');
            $table->foreign('cluster_id')->references('id')->on('clusters')->cascadeOnDelete();

            $table->float('distance')->nullable();

            $table->unique(['candidate_id', 'cluster_id']);
            $table->index('cluster_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_clusters');
    }
};
