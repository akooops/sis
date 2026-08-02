<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An alter rather than a column folded into create_pages_table: `pages` is built
 * on 2026-07-26 and `menus` on 2026-07-27, so the constraint has nothing to point
 * at until here. Folding it in would have meant a column with no foreign key, or
 * renumbering a migration that has already run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->ulid('menu_id')->nullable()->after('content');

            $table->index('menu_id');
            $table->foreign('menu_id')->references('id')->on('menus')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['menu_id']);
            $table->dropIndex(['menu_id']);
            $table->dropColumn('menu_id');
        });
    }
};
