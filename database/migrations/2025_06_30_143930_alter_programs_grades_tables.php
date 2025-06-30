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
        Schema::table('programs', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('slug');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
