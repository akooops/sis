<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streams', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('slug');
            $table->string('color', 7)->default('#1B84FF');
            $table->unsignedInteger('order')->default(0)->index();

            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->json('content')->nullable();
            $table->json('cta')->nullable();

            $table->ulid('program_id');
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();

            $table->unique(['program_id', 'slug']);
            $table->index('program_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
