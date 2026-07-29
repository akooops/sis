<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose rows can belong to a facility mini-site.
     * NULL facility_id means the row belongs to the main website.
     */
    protected array $tables = [
        'pages',
        'articles',
        'albums',
        'events',
        'menus',
        'contact_submissions',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('facility_id')->nullable();
                $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['facility_id']);
                $table->dropColumn('facility_id');
            });
        }
    }
};
