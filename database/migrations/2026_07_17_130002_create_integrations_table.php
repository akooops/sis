<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('driver');
            $table->string('name');
            $table->text('config')->nullable();
            
            $table->boolean('is_enabled')->default(true);

            $table->ulid('integration_type_id');
            $table->foreign('integration_type_id')->references('id')->on('integration_types')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
