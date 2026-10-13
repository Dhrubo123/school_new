<?php

namespace App\Http\Controllers;

use App\Models\ExamMark;
use App\Models\ExamSchedule;
use App\Models\StudentProfile;
use App\Models\TeachingAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TeacherResultsController extends Controller
{
    public function index(Request $request): Response
    {
        $teacher = $request->user()?->teacherProfile;
        abort_unless($teacher, 403);
        $schedules = $this->permittedSchedules($teacher->id);
        $selected = $schedules->firstWhere('id', (int) $request->query('schedule')) ?? $schedules->first();
        $roster = collect();

        if ($selected) {
            $existing = $selected->marks()->get()->keyBy('student_profile_id');
            $roster = StudentProfile::query()
                ->with('user:id,name,email')
                ->where('academic_year_id', $selected->examination->academic_year_id)
                ->where('school_class_id', $selected->school_class_id)
                ->where('school_section_id', $selected->school_section_id)
                ->orderBy('student_no')
                ->get()
                ->map(fn (StudentProfile $student) => [
                    'id' => $student->id,
                    'student_no' => $student->student_no,
                    'name' => $student->user?->name,
                    'status' => $existing->get($student->id)?->status ?? 'present',
                    'marks_obtained' => $existing->get($student->id)?->marks_obtained,
                    'remarks' => $existing->get($student->id)?->remarks,
                ]);
        }

        return Inertia::render('Teacher/Results', [
            'schedules' => $schedules->map(fn (ExamSchedule $schedule) => [
                'id' => $schedule->id,
                'label' => "{$schedule->examination->title} · {$schedule->subject->name} · {$schedule->schoolClass->name} {$schedule->schoolSection->name}",
                'status' => $schedule->examination->status,
            ])->values(),
            'selectedSchedule' => $selected ? [
                'id' => $selected->id,
                'examination' => $selected->examination->only('id', 'title', 'type', 'status'),
                'subject' => $selected->subject->only('id', 'name', 'code'),
                'school_class' => $selected->schoolClass->only('id', 'name'),
                'school_section' => $selected->schoolSection->only('id', 'name'),
                'exam_date' => $selected->exam_date?->toDateString(),
                'full_marks' => (float) $selected->full_marks,
                'pass_marks' => (float) $selected->pass_marks,
            ] : null,
            'roster' => $roster->values(),
        ]);
    }

    public function store(Request $request, ExamSchedule $schedule): RedirectResponse
    {
        $teacher = $request->user()?->teacherProfile;
        abort_unless($teacher && $this->permittedSchedules($teacher->id)->contains('id', $schedule->id), 403);
        $schedule->load('examination');
        if ($schedule->examination->status === 'published') {
            throw ValidationException::withMessages(['schedule' => 'Published results cannot be changed until an administrator unpublishes them.']);
        }

        $data = $request->validate([
            'marks' => ['required', 'array'],
            'marks.*.student_profile_id' => ['required', 'integer', 'distinct', Rule::exists('student_profiles', 'id')],
            'marks.*.status' => ['required', Rule::in(['present', 'absent'])],
            'marks.*.marks_obtained' => ['nullable', 'numeric', 'min:0'],
            'marks.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $studentIds = StudentProfile::query()
            ->where('academic_year_id', $schedule->examination->academic_year_id)
            ->where('school_class_id', $schedule->school_class_id)
            ->where('school_section_id', $schedule->school_section_id)
            ->pluck('id')
            ->sort()
            ->values();
        $submittedIds = collect($data['marks'])->pluck('student_profile_id')->sort()->values();
        if ($studentIds->all() !== $submittedIds->all()) {
            throw ValidationException::withMessages(['marks' => 'Mark every current student in the selected class and section.']);
        }

        foreach ($data['marks'] as $index => $mark) {
            if ($mark['status'] === 'present' && ($mark['marks_obtained'] === null || (float) $mark['marks_obtained'] > (float) $schedule->full_marks)) {
                throw ValidationException::withMessages(["marks.{$index}.marks_obtained" => "Enter a score from 0 to {$schedule->full_marks} for each present student."]);
            }
        }

        DB::transaction(function () use ($request, $schedule, $data) {
            foreach ($data['marks'] as $mark) {
                ExamMark::query()->updateOrCreate(
                    ['exam_schedule_id' => $schedule->id, 'student_profile_id' => $mark['student_profile_id']],
                    [
                        'status' => $mark['status'],
                        'marks_obtained' => $mark['status'] === 'present' ? $mark['marks_obtained'] : null,
                        'remarks' => $mark['remarks'] ?? null,
                        'marked_by' => $request->user()->id,
                        'marked_at' => now(),
                    ],
                );
            }
            DB::table('activity_logs')->insert([
                'user_id' => $request->user()->id,
                'action' => 'exam-marks.saved',
                'subject_type' => ExamSchedule::class,
                'subject_id' => $schedule->id,
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Marks saved successfully.');
    }

    private function permittedSchedules(int $teacherProfileId): Collection
    {
        $assignments = TeachingAssignment::query()->where('teacher_profile_id', $teacherProfileId)->get();

        return ExamSchedule::query()
            ->with(['examination:id,academic_year_id,title,type,status', 'subject:id,name,code', 'schoolClass:id,name', 'schoolSection:id,name'])
            ->whereHas('examination', fn ($query) => $query->whereIn('status', ['draft', 'reviewing']))
            ->orderByDesc('exam_date')
            ->get()
            ->filter(fn (ExamSchedule $schedule) => $assignments->contains(fn (TeachingAssignment $assignment) => $assignment->academic_year_id === $schedule->examination->academic_year_id
                && $assignment->subject_id === $schedule->subject_id
                && $assignment->school_class_id === $schedule->school_class_id
                && ($assignment->school_section_id === null || $assignment->school_section_id === $schedule->school_section_id)))
            ->values();
    }
}
