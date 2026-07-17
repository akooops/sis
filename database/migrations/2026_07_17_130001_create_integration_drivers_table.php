<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_drivers', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('code')->unique();
            $table->string('icon')->nullable();
            
            $table->json('schema')->nullable();

            $table->ulid('integration_type_id');
            $table->foreign('integration_type_id')->references('id')->on('integration_types')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_drivers');
    }
};
