<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_offers', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('employment_type')->index();
            $table->string('work_mode')->index();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('education_level')->nullable();
            $table->date('start_date')->nullable();
            $table->dateTime('deadline_at')->nullable()->index();

            $table->string('status')->default('draft')->index();
            $table->dateTime('published_at')->nullable();

            $table->string('css_url')->nullable();
            $table->text('custom_css')->nullable();

            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->json('content')->nullable();
            $table->json('address')->nullable();
            $table->json('skills')->nullable();

            $table->ulid('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();

            $table->index(['status', 'published_at']);
            $table->index('category_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_offers');
    }
};
