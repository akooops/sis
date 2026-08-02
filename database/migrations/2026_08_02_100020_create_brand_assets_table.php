<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_assets', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Internal filing label; `title` below is the public one.
            $table->string('name');
            $table->unsignedInteger('order')->default(0)->index();

            // Public label, per locale (spatie/laravel-translatable).
            $table->json('title')->nullable();

            $table->ulid('brand_asset_group_id');
            $table->foreign('brand_asset_group_id')->references('id')->on('brand_asset_groups')->cascadeOnDelete();

            $table->index('brand_asset_group_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_assets');
    }
};
