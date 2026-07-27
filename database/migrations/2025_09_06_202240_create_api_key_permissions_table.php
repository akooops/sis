<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_key_permissions', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('api_key_id');
            $table->foreign('api_key_id')->references('id')->on('api_keys')->cascadeOnDelete();

            $table->ulid('permission_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();

            $table->unique(['api_key_id', 'permission_id']);

            $table->index('api_key_id');
            $table->index('permission_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_key_permissions');
    }
};
