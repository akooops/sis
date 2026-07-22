<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_group_users', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('notification_group_id');
            $table->foreign('notification_group_id', 'ngu_group_fk')->references('id')->on('notification_groups')->cascadeOnDelete();

            $table->ulid('user_id');
            $table->foreign('user_id', 'ngu_user_fk')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['notification_group_id', 'user_id'], 'ngu_group_user_unique');

            $table->index('notification_group_id');
            $table->index('user_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_group_users');
    }
};
