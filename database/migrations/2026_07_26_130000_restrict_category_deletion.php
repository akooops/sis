<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A category is now mandatory on articles and achievements, so nullOnDelete is
 * incoherent: deleting a category would leave rows in a state their own
 * validation rejects, and the next save of an untouched record would fail.
 *
 * Restrict instead — a category in use cannot be deleted. CategoriesController
 * turns that into a 422 before the database ever raises it.
 *
 * The columns stay NULLABLE: existing rows may predate this, and a NOT NULL
 * migration would fail on them. Mandatory is enforced at the validation layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['articles', 'achievements'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                // A STRING is the literal constraint name; an array would make
                // Laravel derive one from the columns and prefix it a second time.
                $blueprint->dropForeign($table.'_category_id_foreign');
                $blueprint->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['articles', 'achievements'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                // A STRING is the literal constraint name; an array would make
                // Laravel derive one from the columns and prefix it a second time.
                $blueprint->dropForeign($table.'_category_id_foreign');
                $blueprint->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            });
        }
    }
};
