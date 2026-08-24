<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Which photo albums belong on a venue's page. The twin of facility_articles. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_albums', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('facility_id');
            $table->foreign('facility_id', 'fal_facility_fk')->references('id')->on('facilities')->cascadeOnDelete();

            $table->ulid('album_id');
            $table->foreign('album_id', 'fal_album_fk')->references('id')->on('albums')->cascadeOnDelete();

            $table->unique(['facility_id', 'album_id'], 'fal_facility_album_unique');

            $table->index('facility_id', 'fal_facility_idx');
            $table->index('album_id', 'fal_album_idx');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_albums');
    }
};
