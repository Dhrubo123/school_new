<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamMark extends Model
{
    protected $fillable = ['exam_schedule_id', 'student_profile_id', 'status', 'marks_obtained', 'remarks', 'marked_by', 'marked_at'];

    protected function casts(): array
    {
        return ['marks_obtained' => 'decimal:2', 'marked_at' => 'datetime'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
