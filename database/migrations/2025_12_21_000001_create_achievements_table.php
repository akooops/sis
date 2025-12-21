<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            
            $table->date('achievement_date');
            $table->enum('status', ['draft', 'published', 'hidden'])->default('draft');

            $table->string('linkable_type')->nullable();
            $table->unsignedBigInteger('linkable_id')->nullable();
            $table->string('url')->nullable();

            $table->unsignedBigInteger('achievement_category_id')->nullable();
            $table->foreign('achievement_category_id')->references('id')->on('achievement_categories')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
