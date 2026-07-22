<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_group_types', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('notification_group_id');
            $table->foreign('notification_group_id', 'ngt_group_fk')->references('id')->on('notification_groups')->cascadeOnDelete();

            $table->ulid('notification_type_id');
            $table->foreign('notification_type_id', 'ngt_type_fk')->references('id')->on('notification_types')->cascadeOnDelete();

            $table->timestamps();

            // Explicit name: the auto-generated one exceeds MySQL's 64-char limit.
            $table->unique(['notification_group_id', 'notification_type_id'], 'ngt_group_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_group_types');
    }
};
