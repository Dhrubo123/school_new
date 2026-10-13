<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\SchoolSection;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeachingAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TeachingAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureManager($request);

        return Inertia::render('Admin/TeachingAssignments/Index', ['assignments' => TeachingAssignment::query()->with(['academicYear:id,name', 'teacherProfile.user:id,name', 'subject:id,name,code', 'schoolClass:id,name', 'schoolSection:id,name'])->latest()->paginate(12)]);
    }

    public function create(Request $request): Response
    {
        $this->ensureManager($request);

        return Inertia::render('Admin/TeachingAssignments/Form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManager($request);
        $data = $request->validate($this->rules());
        $this->ensureSectionMatchesClass($data);
        $this->ensureUnique($data);

        DB::transaction(function () use ($request, $data) {
            $assignment = TeachingAssignment::create($data);
            $this->log($request, 'teaching-assignment.created', $assignment);
        });

        return to_route('admin.teaching-assignments.index')->with('success', 'Teaching assignment created.');
    }

    public function edit(Request $request, TeachingAssignment $assignment): Response
    {
        $this->ensureManager($request);

        return Inertia::render('Admin/TeachingAssignments/Form', [...$this->formData(), 'assignment' => $assignment]);
    }

    public function update(Request $request, TeachingAssignment $assignment): RedirectResponse
    {
        $this->ensureManager($request);
        $data = $request->validate($this->rules());
        $this->ensureSectionMatchesClass($data);
        $this->ensureUnique($data, $assignment->id);

        DB::transaction(function () use ($request, $assignment, $data) {
            $assignment->update($data);
            $this->log($request, 'teaching-assignment.updated', $assignment);
        });

        return to_route('admin.teaching-assignments.index')->with('success', 'Teaching assignment saved.');
    }

    public function destroy(Request $request, TeachingAssignment $assignment): RedirectResponse
    {
        $this->ensureManager($request);
        DB::transaction(function () use ($request, $assignment) {
            $this->log($request, 'teaching-assignment.deleted', $assignment);
            $assignment->delete();
        });

        return to_route('admin.teaching-assignments.index')->with('success', 'Teaching assignment deleted.');
    }

    private function rules(): array
    {
        return ['academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')], 'teacher_profile_id' => ['required', 'integer', Rule::exists('teacher_profiles', 'id')], 'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')], 'school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')], 'school_section_id' => ['nullable', 'integer', Rule::exists('school_sections', 'id')]];
    }

    private function ensureSectionMatchesClass(array $data): void
    {
        if ($data['school_section_id'] && ! SchoolSection::query()->whereKey($data['school_section_id'])->where('school_class_id', $data['school_class_id'])->exists()) {
            throw ValidationException::withMessages(['school_section_id' => 'Choose a section that belongs to the selected class.']);
        }
    }

    private function ensureUnique(array $data, ?int $ignore = null): void
    {
        $query = TeachingAssignment::query()->where('academic_year_id', $data['academic_year_id'])->where('teacher_profile_id', $data['teacher_profile_id'])->where('subject_id', $data['subject_id'])->where('school_class_id', $data['school_class_id']);
        $data['school_section_id'] ? $query->where('school_section_id', $data['school_section_id']) : $query->whereNull('school_section_id');
        if ($ignore) {
            $query->whereKeyNot($ignore);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['subject_id' => 'This teaching assignment already exists.']);
        }
    }

    private function formData(): array
    {
        return ['years' => AcademicYear::query()->orderByDesc('is_current')->orderByDesc('starts_on')->get(['id', 'name']), 'teachers' => TeacherProfile::query()->with('user:id,name')->get(['id', 'user_id', 'designation']), 'subjects' => Subject::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code']), 'classes' => SchoolClass::query()->with('sections:id,school_class_id,name')->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])];
    }

    private function ensureManager(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super-admin', 'admin'), 403);
    }

    private function log(Request $request, string $action, TeachingAssignment $assignment): void
    {
        DB::table('activity_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'subject_type' => TeachingAssignment::class, 'subject_id' => $assignment->id, 'ip_address' => $request->ip(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
