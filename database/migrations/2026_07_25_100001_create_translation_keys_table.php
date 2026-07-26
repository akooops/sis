<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_keys', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('group');
            $table->string('key');

            $table->unique(['group', 'key']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_keys');
    }
};
