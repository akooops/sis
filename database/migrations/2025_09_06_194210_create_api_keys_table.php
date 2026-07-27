<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('prefix', 12);
            $table->string('hash');

            $table->json('allowed_ips')->nullable();

            $table->dateTime('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();

            $table->dateTime('expires_at')->nullable();
            $table->dateTime('revoked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
