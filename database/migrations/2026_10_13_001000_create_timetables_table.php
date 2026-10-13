<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room', 100)->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['academic_year_id', 'school_class_id', 'school_section_id', 'day_of_week'], 'timetable_class_section_day_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
