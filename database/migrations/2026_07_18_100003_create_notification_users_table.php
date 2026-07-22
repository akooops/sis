<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_users', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('notification_id');
            $table->foreign('notification_id', 'notification_users_notification_fk')->references('id')->on('notifications')->cascadeOnDelete();

            $table->ulid('user_id');
            $table->foreign('user_id', 'notification_users_user_fk')->references('id')->on('users')->cascadeOnDelete();

            // Read is per-user: this row is the only record of whether *this* user
            // has seen *this* notification.
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Composite key names are set explicitly: the auto-generated names would
            // otherwise exceed MySQL's 64-char identifier limit.
            $table->unique(['notification_id', 'user_id'], 'notification_users_notification_user_unique');
            // Fast unread-count per user (the bell polls this constantly).
            $table->index(['user_id', 'read_at'], 'notification_users_user_read_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_users');
    }
};
