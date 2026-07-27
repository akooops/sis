<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('status')->default('draft')->index();
            $table->dateTime('published_at')->nullable();

            $table->string('css_url')->nullable();
            $table->text('custom_css')->nullable();

            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->json('content')->nullable();

            $table->index(['status', 'published_at']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
