<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSchedule extends Model
{
    protected $fillable = ['examination_id', 'subject_id', 'school_class_id', 'school_section_id', 'exam_date', 'starts_at', 'duration_minutes', 'room', 'full_marks', 'pass_marks'];

    protected function casts(): array
    {
        return ['exam_date' => 'date', 'full_marks' => 'decimal:2', 'pass_marks' => 'decimal:2'];
    }

    public function examination(): BelongsTo
    {
        return $this->belongsTo(Examination::class);
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

    public function marks(): HasMany
    {
        return $this->hasMany(ExamMark::class);
    }
}
