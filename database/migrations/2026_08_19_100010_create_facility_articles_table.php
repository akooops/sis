<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which news items belong on a venue's page.
 *
 * AN ATTACHMENT, NOT OWNERSHIP. The article stays where it is — it keeps its own
 * URL, it still appears in /articles, and detaching it here changes nothing about
 * it. The old module did the opposite: a nullable facility_id ON the articles
 * table, so an article belonged to one venue OR to the main site and every
 * main-site query had to remember a ->main() scope or leak venue content.
 *
 * A FIRST-CLASS PIVOT with its own ULID primary key, like every other pivot here
 * (candidate_clusters, notification_group_users). That key is why attach() must
 * never be called on the BelongsToMany side: it writes a raw insert that never
 * runs the model, so the id is never generated and MySQL rejects the row. Writes
 * go through the pivot MODEL, which is also what makes the observer fire so the
 * change is audited against the facility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_articles', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('facility_id');
            $table->foreign('facility_id', 'fa_facility_fk')->references('id')->on('facilities')->cascadeOnDelete();

            $table->ulid('article_id');
            $table->foreign('article_id', 'fa_article_fk')->references('id')->on('articles')->cascadeOnDelete();

            // Short explicit names: MySQL's identifier limit is 64 characters and
            // the generated ones run past it.
            $table->unique(['facility_id', 'article_id'], 'fa_facility_article_unique');

            $table->index('facility_id', 'fa_facility_idx');
            $table->index('article_id', 'fa_article_idx');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_articles');
    }
};
