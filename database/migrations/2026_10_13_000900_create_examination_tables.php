<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_rules', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_percentage', 5, 2)->unique();
            $table->string('grade', 10);
            $table->decimal('grade_point', 3, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('title', 160);
            $table->enum('type', ['monthly', 'half_yearly', 'final', 'other']);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->enum('status', ['draft', 'reviewing', 'published'])->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->restrictOnDelete();
            $table->foreignId('school_section_id')->constrained('school_sections')->restrictOnDelete();
            $table->date('exam_date');
            $table->time('starts_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->decimal('full_marks', 7, 2);
            $table->decimal('pass_marks', 7, 2);
            $table->timestamps();
            $table->unique(['examination_id', 'subject_id', 'school_class_id', 'school_section_id'], 'exam_schedule_unique');
        });

        Schema::create('exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['present', 'absent'])->default('present');
            $table->decimal('marks_obtained', 7, 2)->nullable();
            $table->string('remarks', 500)->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();
            $table->unique(['exam_schedule_id', 'student_profile_id'], 'exam_mark_unique');
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->primary(['guardian_id', 'student_profile_id']);
        });

        DB::table('grade_rules')->insert([
            ['min_percentage' => 80, 'grade' => 'A+', 'grade_point' => 5, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['min_percentage' => 70, 'grade' => 'A', 'grade_point' => 4, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['min_percentage' => 60, 'grade' => 'A-', 'grade_point' => 3.5, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['min_percentage' => 50, 'grade' => 'B', 'grade_point' => 3, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['min_percentage' => 40, 'grade' => 'C', 'grade_point' => 2, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['min_percentage' => 33, 'grade' => 'D', 'grade_point' => 1, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['min_percentage' => 0, 'grade' => 'F', 'grade_point' => 0, 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('exam_marks');
        Schema::dropIfExists('exam_schedules');
        Schema::dropIfExists('examinations');
        Schema::dropIfExists('grade_rules');
    }
};
