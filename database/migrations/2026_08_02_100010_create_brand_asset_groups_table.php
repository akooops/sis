<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_asset_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->unsignedInteger('order')->default(0)->index();

            $table->json('title')->nullable();

            $table->ulid('brand_id');
            $table->foreign('brand_id')->references('id')->on('brands')->cascadeOnDelete();

            // Unique within the brand only: two brands may both have a "Fonts" group.
            $table->unique(['brand_id', 'name']);
            $table->index('brand_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_asset_groups');
    }
};
