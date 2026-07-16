<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('collection_name');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->string('name');
            $table->string('file_name')->unique();

            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');

            $table->string('disk');

            $table->string('state')->nullable();

            $table->string('model_type')->nullable();
            $table->ulid('model_id')->nullable();

            $table->index('state');
            $table->index(['model_type', 'model_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
