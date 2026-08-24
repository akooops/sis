<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A VENUE the school rents out — a hall, a lab, a sports complex.
 *
 * A content module first and a bookable thing second, which is why it is shaped
 * like VisitService: `name` is the internal label, title/description/content are
 * translated JSON, and css_url/custom_css style the body an admin writes.
 *
 * DELIBERATELY SMALLER THAN THE OLD MODULE. That one gave every venue a
 * subdomain, a theme, its own menus and its own page tree, and bolted a
 * `facility_id` column onto six content tables so a page could belong to one.
 * This is a listing, a detail page and two forms. What a venue "has" is now
 * expressed by the two pivot tables beside this one, which attach EXISTING
 * articles and albums rather than moving them out of the main site.
 *
 * Whether anyone can BOOK is derived from the slots, exactly as on VisitService:
 * hiding a venue stops new bookings without touching the ones already made.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('slug')->unique();
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
        Schema::dropIfExists('facilities');
    }
};
