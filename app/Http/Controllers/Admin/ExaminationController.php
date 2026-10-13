<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExaminationRequest;
use App\Models\AcademicYear;
use App\Models\Examination;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ExaminationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Examination::class);
        $status = $request->query('status');

        return Inertia::render('Admin/Examinations/Index', [
            'status' => $status,
            'statusOptions' => Examination::STATUSES,
            'examinations' => Examination::query()
                ->with('academicYear:id,name')
                ->withCount('schedules')
                ->when(in_array($status, Examination::STATUSES, true), fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Examination::class);

        return Inertia::render('Admin/Examinations/Form', $this->formData());
    }

    public function store(ExaminationRequest $request): RedirectResponse
    {
        $examination = DB::transaction(function () use ($request) {
            $examination = Examination::create([...$request->validated(), 'created_by' => $request->user()->id]);
            $this->log($request, 'examination.created', $examination);

            return $examination;
        });

        return to_route('admin.examinations.edit', $examination)->with('success', 'Examination created. Add its subject schedules next.');
    }

    public function show(Examination $examination): Response
    {
        $this->authorize('view', $examination);
        $schedules = $examination->schedules()
            ->with(['subject:id,name,code', 'schoolClass:id,name', 'schoolSection:id,name'])
            ->withCount('marks')
            ->orderBy('exam_date')
            ->get()
            ->map(function ($schedule) use ($examination) {
                $schedule->student_count = StudentProfile::query()
                    ->where('academic_year_id', $examination->academic_year_id)
                    ->where('school_class_id', $schedule->school_class_id)
                    ->where('school_section_id', $schedule->school_section_id)
                    ->count();

                return $schedule;
            });

        return Inertia::render('Admin/Examinations/Show', [
            'examination' => $examination->load('academicYear:id,name'),
            'schedules' => $schedules,
            'gradeRules' => DB::table('grade_rules')->orderByDesc('min_percentage')->get(['id', 'min_percentage', 'grade', 'grade_point']),
        ]);
    }

    public function edit(Examination $examination): Response
    {
        $this->authorize('update', $examination);

        return Inertia::render('Admin/Examinations/Form', [...$this->formData(), 'examination' => $examination]);
    }

    public function update(ExaminationRequest $request, Examination $examination): RedirectResponse
    {
        $this->assertEditable($examination);

        DB::transaction(function () use ($request, $examination) {
            $examination->update($request->validated());
            $this->log($request, 'examination.updated', $examination);
        });

        return to_route('admin.examinations.show', $examination)->with('success', 'Examination details saved.');
    }

    public function destroy(Request $request, Examination $examination): RedirectResponse
    {
        $this->authorize('delete', $examination);
        $this->assertEditable($examination);

        DB::transaction(function () use ($request, $examination) {
            $this->log($request, 'examination.deleted', $examination);
            $examination->delete();
        });

        return to_route('admin.examinations.index')->with('success', 'Examination deleted.');
    }

    public function changeStatus(Request $request, Examination $examination): RedirectResponse
    {
        $this->authorize('update', $examination);
        $data = $request->validate(['action' => ['required', Rule::in(['review', 'publish', 'unpublish'])]]);

        if (in_array($data['action'], ['review', 'publish'], true)) {
            $this->ensureComplete($examination);
        }

        DB::transaction(function () use ($request, $examination, $data) {
            if ($data['action'] === 'review') {
                $examination->update(['status' => 'reviewing']);
            } elseif ($data['action'] === 'publish') {
                $examination->update(['status' => 'published', 'published_at' => now()]);
            } else {
                $examination->update(['status' => 'draft', 'published_at' => null]);
            }
            $this->log($request, "examination.{$data['action']}ed", $examination);
        });

        return back()->with('success', $data['action'] === 'publish' ? 'Results are now published for students and guardians.' : 'Examination status updated.');
    }

    private function ensureComplete(Examination $examination): void
    {
        $schedules = $examination->schedules()->withCount('marks')->get();
        if ($schedules->isEmpty()) {
            throw ValidationException::withMessages(['results' => 'Add at least one subject schedule before reviewing results.']);
        }

        foreach ($schedules as $schedule) {
            $students = StudentProfile::query()
                ->where('academic_year_id', $examination->academic_year_id)
                ->where('school_class_id', $schedule->school_class_id)
                ->where('school_section_id', $schedule->school_section_id)
                ->count();
            if ($students === 0 || $schedule->marks_count !== $students) {
                throw ValidationException::withMessages(['results' => "Enter marks for every student in {$schedule->subject()->value('name')} before reviewing or publishing results."]);
            }
        }
    }

    private function assertEditable(Examination $examination): void
    {
        if ($examination->status === 'published') {
            throw ValidationException::withMessages(['examination' => 'Unpublish results before changing this examination.']);
        }
    }

    private function formData(): array
    {
        return ['years' => AcademicYear::query()->orderByDesc('is_current')->orderByDesc('starts_on')->get(['id', 'name']), 'types' => Examination::TYPES];
    }

    private function log(Request $request, string $action, Examination $examination): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => Examination::class,
            'subject_id' => $examination->id,
            'properties' => json_encode(['status' => $examination->status]),
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
