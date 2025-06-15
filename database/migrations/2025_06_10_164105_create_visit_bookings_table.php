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
        Schema::create('visit_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('guardian_name');
            $table->string('email');
            $table->string('phone', 20);

            $table->string('student_name');
            $table->string('student_grade');
            $table->string('student_school');

            $table->integer('visitors_count')->default(1);

            $table->unsignedBigInteger('visit_service_id')->nullable();
            $table->foreign('visit_service_id')->references('id')->on('visit_services')->cascadeOnDelete();
            
            $table->unsignedBigInteger('visit_time_slot_id')->nullable();
            $table->foreign('visit_time_slot_id')->references('id')->on('visit_time_slots')->cascadeOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_bookings');
    }
};
