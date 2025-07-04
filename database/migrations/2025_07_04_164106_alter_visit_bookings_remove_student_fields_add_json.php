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
        Schema::table('visit_bookings', function (Blueprint $table) {
            // Remove individual student fields
            $table->dropColumn(['student_name', 'student_grade', 'student_school']);
            
            // Add students as JSON field
            $table->json('students')->nullable()->after('visitors_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_bookings', function (Blueprint $table) {
            // Remove JSON field
            $table->dropColumn('students');
            
            // Add back individual student fields
            $table->string('student_name')->after('phone');
            $table->string('student_grade')->after('student_name');
            $table->string('student_school')->after('student_grade');
        });
    }
}; 