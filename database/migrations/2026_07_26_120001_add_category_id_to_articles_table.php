<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Nullable: articles predate categories, and an uncategorised article
            // is a legitimate state rather than an error. nullOnDelete so removing
            // a category uncategorises its articles instead of taking them with it.
            $table->foreignUlid('category_id')->nullable()->after('slug')->constrained('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
