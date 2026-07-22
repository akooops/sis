<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_group_user_integrations', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // The membership (a user in a notification group) this delivery
            // preference belongs to.
            $table->ulid('notification_group_user_id');
            $table->foreign('notification_group_user_id', 'ngui_membership_fk')->references('id')->on('notification_group_users')->cascadeOnDelete();

            $table->ulid('integration_id');
            $table->foreign('integration_id', 'ngui_integration_fk')->references('id')->on('integrations')->cascadeOnDelete();

            $table->timestamps();

            // Explicit names: the auto-generated identifiers exceed MySQL's 64-char limit.
            $table->unique(['notification_group_user_id', 'integration_id'], 'ngui_membership_integration_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_group_user_integrations');
    }
};
