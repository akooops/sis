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
        Schema::create('job_application_languages', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->enum('proficiency', ['basic', 'intermediate', 'advanced', 'native']);

            $table->unsignedBigInteger('job_application_id')->nullable();
            $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_application_languages');
    }
};
