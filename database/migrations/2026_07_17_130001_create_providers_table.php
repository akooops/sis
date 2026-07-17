<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('provider_type_code');
            $table->string('driver');
            $table->string('name');

            // Non-secret config (host, port, sender id…) stays plaintext and
            // queryable; secrets live in `credentials`, an encrypted:array blob
            // (never queried, never logged).
            $table->json('config')->nullable();
            $table->text('credentials')->nullable();

            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_default')->default(false);

            // Last connection-test outcome, surfaced as a status dot in the UI.
            $table->dateTime('last_tested_at')->nullable();
            $table->boolean('last_test_ok')->nullable();
            $table->text('last_test_error')->nullable();

            $table->timestamps();

            $table->foreign('provider_type_code')->references('code')->on('provider_types')->cascadeOnDelete();
            $table->index(['provider_type_code', 'is_default']);
            $table->index(['provider_type_code', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
