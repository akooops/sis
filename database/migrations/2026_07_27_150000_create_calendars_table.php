<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendars', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');

            $table->date('start_date');
            $table->date('end_date');

            $table->boolean('is_active')->default(true)->index();

            $table->json('title')->nullable();

            $table->index(['start_date', 'end_date']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendars');
    }
};
