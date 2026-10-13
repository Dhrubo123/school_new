<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingAssignment extends Model
{
    protected $fillable = ['academic_year_id', 'teacher_profile_id', 'subject_id', 'school_class_id', 'school_section_id'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function schoolSection(): BelongsTo
    {
        return $this->belongsTo(SchoolSection::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }
}
