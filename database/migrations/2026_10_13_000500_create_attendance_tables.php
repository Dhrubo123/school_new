<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('teaching_assignment_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->restrictOnDelete();
            $table->foreignId('school_section_id')->constrained('school_sections')->restrictOnDelete();
            $table->date('attendance_date')->index();
            $table->string('period_label', 80);
            $table->foreignId('taken_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['teaching_assignment_id', 'school_section_id', 'attendance_date', 'period_label'], 'attendance_session_unique');
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present')->index();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->unique(['attendance_session_id', 'student_profile_id'], 'attendance_record_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
    }
};
