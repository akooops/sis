<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('code')->unique();

            $table->boolean('is_default')->default(false);

            $table->json('title')->nullable();
            $table->json('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_groups');
    }
};
