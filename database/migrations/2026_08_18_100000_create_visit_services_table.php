<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A BOOKABLE VISIT — "Early Learning tour", "High School tour".
 *
 * Shaped like JobOffer because it is the same kind of thing: a content module
 * with translated copy, a thumbnail, its own editorial clock, and a public page.
 * `name` is the internal label; title/description/content are translated JSON.
 *
 * `content` is the long body an admin writes for the Read-more popup, and
 * css_url/custom_css are what let that popup be styled per service without a
 * developer — the same pair JobOffer carries, scoped the same way.
 *
 * TWO CLOCKS, as on JobOffer, and they are not the same thing: status/published_at
 * are editorial (is this service offered at all), while whether anyone can book
 * is derived from its SLOTS. A published service with no future open slot renders
 * its empty state; nothing flips status when the last slot passes.
 *
 * `max_visitors` caps the party-size counter on the public card. It was hardcoded
 * 1..5 in the old app; a school that runs a family-only tour needs 2, and one
 * running a group visit needs 30.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_services', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('slug')->unique();

            /* Display only — the real length of a booking is its slot's span.
               Two numbers rather than one because the card advertises "60 min"
               before any slot has been picked. */
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedTinyInteger('max_visitors')->default(5);
            $table->unsignedInteger('order')->default(0);

            $table->string('status')->default('draft')->index();
            $table->dateTime('published_at')->nullable();

            $table->string('css_url')->nullable();
            $table->text('custom_css')->nullable();

            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->json('content')->nullable();

            $table->index(['status', 'published_at']);
            $table->index('order');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_services');
    }
};
