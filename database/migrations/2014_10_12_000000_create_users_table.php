<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('azure_ad_id')->nullable()->unique();

            $table->string('firstname');
            $table->string('lastname');

            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('password');

            $table->string('status')->default('pending')->index();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
