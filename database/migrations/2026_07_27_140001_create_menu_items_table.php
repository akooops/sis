<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('url')->nullable();
            $table->unsignedInteger('order')->default(0);

            $table->string('linkable_type')->nullable();
            $table->ulid('linkable_id')->nullable();

            $table->json('title')->nullable();

            $table->ulid('menu_id');
            $table->foreign('menu_id')->references('id')->on('menus')->cascadeOnDelete();

            $table->ulid('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('menu_items')->nullOnDelete();

            $table->index(['linkable_type', 'linkable_id']);
            $table->index(['menu_id', 'parent_id', 'order']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
