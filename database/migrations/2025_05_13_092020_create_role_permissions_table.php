<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('role_id')->nullable();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();

            $table->unsignedBigInteger('permission_id')->nullable();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
