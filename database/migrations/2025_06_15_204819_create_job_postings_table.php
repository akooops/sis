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
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();

            $table->enum('employment_type', ['full_time', 'part_time', 'internship']);
            $table->boolean('is_remote')->default(false);
            $table->integer('number_of_positions')->default(1);

            $table->integer('required_years_of_experience')->nullable();
            $table->date('application_deadline')->nullable();

            $table->enum('status', ['draft', 'published', 'hidden'])->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
