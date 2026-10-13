<?php

namespace App\Services;

use App\Models\Examination;
use App\Models\ExamSchedule;
use App\Models\GradeRule;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class ResultCalculator
{
    public function report(StudentProfile $student, Examination $examination): array
    {
        $rules = GradeRule::query()->orderByDesc('min_percentage')->get();
        $schedules = ExamSchedule::query()
            ->with(['subject:id,name,code', 'marks' => fn ($query) => $query->where('student_profile_id', $student->id)])
            ->where('examination_id', $examination->id)
            ->where('school_class_id', $student->school_class_id)
            ->where('school_section_id', $student->school_section_id)
            ->orderBy('exam_date')
            ->orderBy('id')
            ->get();

        $subjects = $schedules->map(fn (ExamSchedule $schedule) => $this->subjectResult($schedule, $rules));
        $entered = $subjects->where('entered', true);
        $complete = $subjects->isNotEmpty() && $entered->count() === $subjects->count();
        $failed = $entered->contains(fn (array $subject) => ! $subject['passed']);
        $fullMarks = (float) $subjects->sum('full_marks');
        $obtainedMarks = (float) $entered->sum('marks_obtained');
        $percentage = $fullMarks > 0 && $complete ? round(($obtainedMarks / $fullMarks) * 100, 2) : null;
        $gpa = $complete ? ($failed ? 0.0 : round((float) $entered->avg('grade_point'), 2)) : null;
        $overallRule = $percentage === null ? null : $this->ruleFor($rules, $percentage);

        return [
            'subjects' => $subjects->values()->all(),
            'summary' => [
                'complete' => $complete,
                'subject_count' => $subjects->count(),
                'full_marks' => $fullMarks,
                'obtained_marks' => $complete ? $obtainedMarks : null,
                'percentage' => $percentage,
                'gpa' => $gpa,
                'grade' => $complete ? ($failed ? 'F' : $overallRule?->grade) : null,
                'result' => $complete ? ($failed ? 'Not passed' : 'Passed') : 'Results are incomplete',
            ],
        ];
    }

    private function subjectResult(ExamSchedule $schedule, Collection $rules): array
    {
        $mark = $schedule->marks->first();
        $entered = $mark !== null;
        $present = $mark?->status === 'present';
        $marks = $mark?->marks_obtained === null ? null : (float) $mark->marks_obtained;
        $fullMarks = (float) $schedule->full_marks;
        $passMarks = (float) $schedule->pass_marks;
        $percentage = $present && $marks !== null && $fullMarks > 0 ? round(($marks / $fullMarks) * 100, 2) : 0.0;
        $passed = $entered && $present && $marks !== null && $marks >= $passMarks;
        $rule = $this->ruleFor($rules, $percentage);

        return [
            'schedule_id' => $schedule->id,
            'subject' => $schedule->subject?->name,
            'subject_code' => $schedule->subject?->code,
            'exam_date' => $schedule->exam_date?->toDateString(),
            'full_marks' => $fullMarks,
            'pass_marks' => $passMarks,
            'entered' => $entered,
            'status' => $mark?->status,
            'marks_obtained' => $marks,
            'percentage' => $entered ? $percentage : null,
            'grade' => ! $entered ? null : ($passed ? $rule?->grade : 'F'),
            'grade_point' => ! $entered ? null : ($passed ? (float) ($rule?->grade_point ?? 0) : 0.0),
            'passed' => $passed,
            'remarks' => $mark?->remarks,
        ];
    }

    private function ruleFor(Collection $rules, float $percentage): ?GradeRule
    {
        return $rules->first(fn (GradeRule $rule) => $percentage >= (float) $rule->min_percentage);
    }
}
