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

            // NotificationType code, resolved by code (like integrations.driver).
            $table->string('type');

            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();

            // A Ziggy route + params so the click-through link can be built later.
            $table->string('route_name')->nullable();
            $table->json('route_params')->nullable();

            // Overrides the type's icon when set.
            $table->string('icon')->nullable();

            // Optional subject (the thing the notification is about).
            $table->nullableUlidMorphs('notifiable');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
