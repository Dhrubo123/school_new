<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = ['attendance_session_id', 'student_profile_id', 'status', 'note'];

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }
}
