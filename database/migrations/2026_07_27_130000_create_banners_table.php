<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Internal label; `title` below is the public one.
            $table->string('name');
            $table->unsignedInteger('order')->default(0)->index();

            // An external link, or an internal record, or neither — never both.
            $table->string('url')->nullable();
            $table->string('linkable_type')->nullable();
            $table->ulid('linkable_id')->nullable();
            $table->index(['linkable_type', 'linkable_id']);

            $table->json('title')->nullable();
            $table->json('cta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
