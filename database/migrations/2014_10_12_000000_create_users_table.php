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
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('firstname');
            $table->string('lastname');

            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('password');

            $table->string('azure_ad_id')->nullable()->unique();

            // Approval gate: null = pending admin approval, set = approved/active.
            $table->dateTime('verified_at')->nullable();
            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
