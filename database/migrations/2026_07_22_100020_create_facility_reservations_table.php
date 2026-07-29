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
        Schema::create('facility_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20);
            $table->integer('guests_count')->default(1);
            $table->text('message')->nullable();

            $table->unsignedBigInteger('facility_id')->nullable();
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();

            $table->unsignedBigInteger('facility_time_slot_id')->nullable();
            $table->foreign('facility_time_slot_id')->references('id')->on('facility_time_slots')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_reservations');
    }
};
