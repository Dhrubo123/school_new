<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    protected $fillable = ['academic_year_id', 'teaching_assignment_id', 'teacher_profile_id', 'school_class_id', 'school_section_id', 'attendance_date', 'period_label', 'taken_by'];

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function schoolSection(): BelongsTo
    {
        return $this->belongsTo(SchoolSection::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
