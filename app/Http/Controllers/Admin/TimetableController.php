<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TimetableRequest;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\SchoolSection;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Timetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TimetableController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Timetable::class);
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer'],
            'school_class_id' => ['nullable', 'integer'],
            'school_section_id' => ['nullable', 'integer'],
        ]);

        return Inertia::render('Admin/Timetables/Index', [
            ...$this->formData(),
            'filters' => $filters,
            'timetables' => Timetable::query()
                ->with(['academicYear:id,name', 'schoolClass:id,name', 'schoolSection:id,name', 'subject:id,name,code', 'teacherProfile.user:id,name'])
                ->when($filters['academic_year_id'] ?? null, fn ($query, $value) => $query->where('academic_year_id', $value))
                ->when($filters['school_class_id'] ?? null, fn ($query, $value) => $query->where('school_class_id', $value))
                ->when($filters['school_section_id'] ?? null, fn ($query, $value) => $query->where('school_section_id', $value))
                ->weeklyOrder()
                ->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Timetable::class);

        return Inertia::render('Admin/Timetables/Form', $this->formData());
    }

    public function store(TimetableRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->validateSchedule($data);

        DB::transaction(function () use ($request, $data) {
            $timetable = Timetable::create($data);
            $this->log($request, 'timetable.created', $timetable);
        });

        return to_route('admin.timetables.index')->with('success', 'Class routine entry created.');
    }

    public function edit(Timetable $timetable): Response
    {
        $this->authorize('update', $timetable);

        return Inertia::render('Admin/Timetables/Form', [...$this->formData(), 'timetable' => $timetable]);
    }

    public function update(TimetableRequest $request, Timetable $timetable): RedirectResponse
    {
        $this->authorize('update', $timetable);
        $data = $request->validated();
        $this->validateSchedule($data, $timetable);

        DB::transaction(function () use ($request, $timetable, $data) {
            $timetable->update($data);
            $this->log($request, 'timetable.updated', $timetable);
        });

        return to_route('admin.timetables.index')->with('success', 'Class routine entry updated.');
    }

    public function destroy(Request $request, Timetable $timetable): RedirectResponse
    {
        $this->authorize('delete', $timetable);

        DB::transaction(function () use ($request, $timetable) {
            $this->log($request, 'timetable.deleted', $timetable);
            $timetable->delete();
        });

        return back()->with('success', 'Class routine entry deleted.');
    }

    private function formData(): array
    {
        return [
            'years' => AcademicYear::query()->orderByDesc('is_current')->orderByDesc('starts_on')->get(['id', 'name', 'is_current']),
            'classes' => SchoolClass::query()->with('sections:id,school_class_id,name')->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code']),
            'teachers' => TeacherProfile::query()->with('user:id,name')->get(['id', 'user_id', 'designation']),
            'days' => Timetable::DAYS,
        ];
    }

    private function validateSchedule(array $data, ?Timetable $ignore = null): void
    {
        if (! SchoolSection::query()->whereKey($data['school_section_id'])->where('school_class_id', $data['school_class_id'])->exists()) {
            throw ValidationException::withMessages(['school_section_id' => 'Choose a section that belongs to the selected class.']);
        }

        $overlap = fn ($query) => $query->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at']);
        $classConflict = Timetable::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('school_class_id', $data['school_class_id'])
            ->where('school_section_id', $data['school_section_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore))
            ->where($overlap)
            ->exists();
        if ($classConflict) {
            throw ValidationException::withMessages(['starts_at' => 'This section already has a class during those times.']);
        }

        if ($data['teacher_profile_id'] ?? null) {
            $teacherConflict = Timetable::query()
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('teacher_profile_id', $data['teacher_profile_id'])
                ->where('day_of_week', $data['day_of_week'])
                ->when($ignore, fn ($query) => $query->whereKeyNot($ignore))
                ->where($overlap)
                ->exists();
            if ($teacherConflict) {
                throw ValidationException::withMessages(['teacher_profile_id' => 'This teacher is already assigned to another class during those times.']);
            }
        }
    }

    private function log(Request $request, string $action, Timetable $timetable): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => Timetable::class,
            'subject_id' => $timetable->id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
