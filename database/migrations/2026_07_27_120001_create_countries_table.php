<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->char('code', 2)->unique();
            $table->char('alpha3', 3)->nullable()->unique();

            $table->string('flag')->nullable();

            $table->boolean('is_enabled')->default(true)->index();

            $table->json('title')->nullable();
            $table->json('nationality')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
