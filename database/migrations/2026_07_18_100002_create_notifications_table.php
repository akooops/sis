<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('title');
            $table->text('body')->nullable();

            $table->string('route_name')->nullable();
            $table->json('route_params')->nullable();

            $table->ulid('notification_type_id');
            $table->foreign('notification_type_id')->references('id')->on('notification_types')->cascadeOnDelete();

            $table->index('notification_type_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
