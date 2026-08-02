<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Countries a form refuses. Resolved from the visitor's IP and enforced on the
 * GET as well as the POST — rendering a form somebody is not allowed to submit
 * is just a slower rejection.
 *
 * No on/off flag: an empty set blocks nothing, which is the same thing "off"
 * would mean. A flag plus a list can disagree, and then nobody can tell from the
 * row whether the block is live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_blocked_countries', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('form_id');
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();

            $table->ulid('country_id');
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();

            $table->unique(['form_id', 'country_id']);
            $table->index('country_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_blocked_countries');
    }
};
