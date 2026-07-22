<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_group_notification_types', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('notification_group_id');
            $table->foreign('notification_group_id', 'ngnt_group_fk')->references('id')->on('notification_groups')->cascadeOnDelete();

            $table->ulid('notification_type_id');
            $table->foreign('notification_type_id', 'ngnt_type_fk')->references('id')->on('notification_types')->cascadeOnDelete();

            $table->unique(['notification_group_id', 'notification_type_id'], 'ngnt_group_type_unique');

            $table->index('notification_group_id', 'ngnt_group_index');
            $table->index('notification_type_id', 'ngnt_type_index');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_group_notification_types');
    }
};
