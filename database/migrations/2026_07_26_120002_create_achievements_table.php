<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('slug')->unique();

            $table->foreignUlid('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->json('content')->nullable();
            // Who achieved it — translatable, because a name or team renders
            // differently per language.
            $table->json('done_by')->nullable();

            $table->string('status')->default('draft')->index();
            $table->dateTime('published_at')->nullable();

            // When the achievement happened — unrelated to when its page goes live.
            $table->date('achieved_at');

            $table->string('css_url')->nullable();
            $table->text('custom_css')->nullable();

            $table->index(['status', 'published_at']);
            $table->index('achieved_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
