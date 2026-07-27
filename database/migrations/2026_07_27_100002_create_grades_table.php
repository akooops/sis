<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->unsignedInteger('order')->default(0)->index();

            $table->json('title')->nullable();

            $table->ulid('program_id');
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();

            $table->index('program_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
