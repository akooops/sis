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
        Schema::table('job_applications', function (Blueprint $table) {
            $table->text('ai_score_explanation')->nullable()->after('ai_score');
            $table->enum('ai_score_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->after('ai_score_explanation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn('ai_score_explanation');
            $table->dropColumn('ai_score_status');
        });
    }
};
