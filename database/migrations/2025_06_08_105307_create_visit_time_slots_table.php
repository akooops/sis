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
        Schema::create('visit_time_slots', function (Blueprint $table) {
            $table->id();
            $table->dateTime('starts_at');

            $table->unsignedBigInteger('visit_service_id')->nullable();
            $table->foreign('visit_service_id')->references('id')->on('visit_services')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_time_slots');
    }
};
