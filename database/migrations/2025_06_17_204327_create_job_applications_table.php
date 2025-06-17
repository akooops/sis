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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 20);
            $table->string('nationality');
            $table->text('address');
            $table->text('skills')->nullable();
            
            $table->integer('ai_score')->nullable();
            $table->timestamp('ai_scored_at')->nullable();
            
            $table->unsignedBigInteger('job_posting_id')->nullable();
            $table->foreign('job_posting_id')->references('id')->on('job_postings')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
