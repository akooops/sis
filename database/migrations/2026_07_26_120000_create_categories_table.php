<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('code');

            // App\Enums\CategoryType — what this category classifies.
            $table->string('type')->index();

            // Unique per type, not globally: "general" is a reasonable code for
            // both an article category and an achievement one, and they are
            // separate lists that never mix.
            $table->unique(['type', 'code']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
