<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExamScheduleRequest;
use App\Models\Examination;
use App\Models\ExamSchedule;
use App\Models\SchoolClass;
use App\Models\SchoolSection;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ExamScheduleController extends Controller
{
    public function create(Examination $examination): Response
    {
        $this->authorize('update', $examination);
        $this->assertEditable($examination);

        return Inertia::render('Admin/Examinations/ScheduleForm', [...$this->formData(), 'examination' => $examination]);
    }

    public function store(ExamScheduleRequest $request, Examination $examination): RedirectResponse
    {
        $this->assertEditable($examination);
        $data = $request->validated();
        $this->ensureSectionMatchesClass($data);
        $this->ensureUnique($examination, $data);

        DB::transaction(function () use ($request, $examination, $data) {
            $schedule = $examination->schedules()->create($data);
            $this->log($request, 'exam-schedule.created', $schedule);
        });

        return to_route('admin.examinations.show', $examination)->with('success', 'Subject schedule added.');
    }

    public function edit(Examination $examination, ExamSchedule $schedule): Response
    {
        $this->authorize('update', $examination);
        $this->assertScheduleBelongsToExam($schedule, $examination);
        $this->assertEditable($examination);

        return Inertia::render('Admin/Examinations/ScheduleForm', [...$this->formData(), 'examination' => $examination, 'schedule' => $schedule]);
    }

    public function update(ExamScheduleRequest $request, Examination $examination, ExamSchedule $schedule): RedirectResponse
    {
        $this->assertScheduleBelongsToExam($schedule, $examination);
        $this->assertEditable($examination);
        $data = $request->validated();
        $this->ensureSectionMatchesClass($data);
        $this->ensureUnique($examination, $data, $schedule);

        DB::transaction(function () use ($request, $schedule, $data) {
            $schedule->update($data);
            $this->log($request, 'exam-schedule.updated', $schedule);
        });

        return to_route('admin.examinations.show', $examination)->with('success', 'Subject schedule saved.');
    }

    public function destroy(Request $request, Examination $examination, ExamSchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $examination);
        $this->assertScheduleBelongsToExam($schedule, $examination);
        $this->assertEditable($examination);

        DB::transaction(function () use ($request, $schedule) {
            $this->log($request, 'exam-schedule.deleted', $schedule);
            $schedule->delete();
        });

        return back()->with('success', 'Subject schedule deleted.');
    }

    private function formData(): array
    {
        return [
            'subjects' => Subject::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code']),
            'classes' => SchoolClass::query()->with('sections:id,school_class_id,name')->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function ensureSectionMatchesClass(array $data): void
    {
        if (! SchoolSection::query()->whereKey($data['school_section_id'])->where('school_class_id', $data['school_class_id'])->exists()) {
            throw ValidationException::withMessages(['school_section_id' => 'Choose a section that belongs to the selected class.']);
        }
    }

    private function ensureUnique(Examination $examination, array $data, ?ExamSchedule $ignore = null): void
    {
        $exists = $examination->schedules()
            ->where('subject_id', $data['subject_id'])
            ->where('school_class_id', $data['school_class_id'])
            ->where('school_section_id', $data['school_section_id'])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['subject_id' => 'This subject already has a schedule for the selected class and section.']);
        }

        if (! empty($data['starts_at']) && ! empty($data['duration_minutes'])) {
            $newStart = Carbon::createFromFormat('H:i', $data['starts_at']);
            $newEnd = $newStart->copy()->addMinutes((int) $data['duration_minutes']);
            $sameDaySchedules = $examination->schedules()
                ->where('school_class_id', $data['school_class_id'])
                ->where('school_section_id', $data['school_section_id'])
                ->whereDate('exam_date', $data['exam_date'])
                ->whereNotNull('starts_at')
                ->whereNotNull('duration_minutes')
                ->when($ignore, fn ($query) => $query->whereKeyNot($ignore))
                ->get(['id', 'starts_at', 'duration_minutes']);

            foreach ($sameDaySchedules as $scheduled) {
                $existingStart = Carbon::parse($scheduled->starts_at);
                $existingEnd = $existingStart->copy()->addMinutes((int) $scheduled->duration_minutes);
                if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                    throw ValidationException::withMessages(['starts_at' => 'This class and section already has an overlapping exam at that time.']);
                }
            }
        }
    }

    private function assertScheduleBelongsToExam(ExamSchedule $schedule, Examination $examination): void
    {
        abort_unless($schedule->examination_id === $examination->id, 404);
    }

    private function assertEditable(Examination $examination): void
    {
        if ($examination->status === 'published') {
            throw ValidationException::withMessages(['examination' => 'Unpublish results before changing schedules.']);
        }
        if ($examination->routine_published_at) {
            throw ValidationException::withMessages(['examination' => 'Unpublish the exam routine before changing schedules.']);
        }
    }

    private function log(Request $request, string $action, ExamSchedule $schedule): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => ExamSchedule::class,
            'subject_id' => $schedule->id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
