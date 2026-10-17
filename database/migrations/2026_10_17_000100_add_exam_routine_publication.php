<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->text('routine_instructions')->nullable()->after('ends_on');
            $table->timestamp('routine_published_at')->nullable()->after('published_at')->index();
        });

        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->string('room', 120)->nullable()->after('duration_minutes');
            $table->index(['school_class_id', 'school_section_id', 'exam_date'], 'exam_schedule_portal_index');
        });
    }

    public function down(): void
    {
        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->dropIndex('exam_schedule_portal_index');
            $table->dropColumn('room');
        });

        Schema::table('examinations', function (Blueprint $table) {
            $table->dropIndex(['routine_published_at']);
            $table->dropColumn(['routine_instructions', 'routine_published_at']);
        });
    }
};
