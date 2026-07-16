<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('log_name')->nullable();
            $table->string('event')->nullable();

            $table->text('description');
            $table->json('properties')->nullable();

            $table->string('subject_type')->nullable();
            $table->ulid('subject_id')->nullable();

            $table->string('causer_type')->nullable();
            $table->ulid('causer_id')->nullable();

            $table->uuid('batch_uuid')->nullable();

            $table->index('log_name');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
