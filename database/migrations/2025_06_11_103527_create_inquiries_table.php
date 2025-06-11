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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();

            $table->string('guardian_name');

            $table->string('email');
            $table->string('phone', 20);

            $table->string('student_name');
            $table->date('student_birthdate');
            $table->string('student_school');

            $table->string('academic_year_applied');
            $table->string('grade_applied');
            
            $table->text('questions');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
