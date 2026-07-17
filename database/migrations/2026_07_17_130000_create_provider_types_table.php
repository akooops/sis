<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_types', function (Blueprint $table) {
            // The type code is the key (email, sms…) — a seeded mirror of
            // config('integrations.types'), so providers can FK to it.
            $table->string('code')->primary();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_types');
    }
};
