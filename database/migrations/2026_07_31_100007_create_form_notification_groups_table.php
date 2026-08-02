<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Groups notified when this form receives a submission — this form's own
 * audience, on top of whoever subscribes to the form.submission_received type.
 * ProcessFormSubmission hands these ids to NotificationService::send(), which
 * unions them with the type's subscribers and dedupes per user.
 *
 * The same pivot is edited from both ends — from the form, and from the group's
 * own page — so neither side owns it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_notification_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('form_id');
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();

            $table->ulid('notification_group_id');
            $table->foreign('notification_group_id')->references('id')->on('notification_groups')->cascadeOnDelete();

            $table->unique(['form_id', 'notification_group_id']);
            $table->index('notification_group_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_notification_groups');
    }
};
