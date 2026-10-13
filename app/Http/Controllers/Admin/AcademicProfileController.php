<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\SchoolSection;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcademicProfileController extends Controller
{
    public function index(Request $request, string $type): Response
    {
        $this->ensureManager($request);
        $profileClass = $this->profileClass($type);
        $search = trim((string) $request->query('search'));

        $profiles = $profileClass::query()->with($type === 'teachers' ? ['user:id,name,email'] : ['user:id,name,email', 'academicYear:id,name', 'schoolClass:id,name', 'schoolSection:id,name'])
            ->when($search, fn ($query) => $query->whereHas('user', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/People/Index', ['type' => $type, 'search' => $search, 'profiles' => $profiles]);
    }

    public function create(Request $request, string $type): Response
    {
        $this->ensureManager($request);
        $profileClass = $this->profileClass($type);

        return Inertia::render('Admin/People/Form', [...$this->formData($type), 'type' => $type, 'users' => $this->eligibleUsers($type, $profileClass)]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->ensureManager($request);
        $profileClass = $this->profileClass($type);
        $data = $request->validate($this->rules($type));
        $this->ensureEligibleUser($type, (int) $data['user_id']);
        $this->ensureStudentSectionMatchesClass($type, $data);

        $profile = DB::transaction(function () use ($request, $profileClass, $data, $type) {
            $profile = $profileClass::create($data);
            $this->log($request, 'profile.created', $profile, ['type' => $type]);

            return $profile;
        });

        return to_route('admin.people.index', ['type' => $type])->with('success', $this->label($type).' profile created.');
    }

    public function edit(Request $request, string $type, int $id): Response
    {
        $this->ensureManager($request);
        $profile = $this->find($type, $id);

        return Inertia::render('Admin/People/Form', [...$this->formData($type), 'type' => $type, 'profile' => $profile, 'users' => $this->eligibleUsers($type, $this->profileClass($type), $profile->user_id)]);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $this->ensureManager($request);
        $profile = $this->find($type, $id);
        $data = $request->validate($this->rules($type, $profile));
        $this->ensureEligibleUser($type, (int) $data['user_id']);
        $this->ensureStudentSectionMatchesClass($type, $data);

        DB::transaction(function () use ($request, $profile, $data, $type) {
            $profile->update($data);
            $this->log($request, 'profile.updated', $profile, ['type' => $type]);
        });

        return to_route('admin.people.index', ['type' => $type])->with('success', $this->label($type).' profile saved.');
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        $this->ensureManager($request);
        $profile = $this->find($type, $id);
        if ($type === 'teachers' && $profile->teachingAssignments()->exists()) {
            throw ValidationException::withMessages(['profile' => 'Remove this teacher’s teaching assignments before deleting the profile.']);
        }

        DB::transaction(function () use ($request, $profile, $type) {
            $this->log($request, 'profile.deleted', $profile, ['type' => $type]);
            $profile->delete();
        });

        return to_route('admin.people.index', ['type' => $type])->with('success', $this->label($type).' profile removed. The login account remains available.');
    }

    private function profileClass(string $type): string
    {
        abort_unless(in_array($type, ['teachers', 'students'], true), 404);

        return $type === 'teachers' ? TeacherProfile::class : StudentProfile::class;
    }

    private function find(string $type, int $id): Model
    {
        return $this->profileClass($type)::query()->findOrFail($id);
    }

    private function eligibleUsers(string $type, string $profileClass, ?int $currentUserId = null)
    {
        $role = $type === 'teachers' ? 'teacher' : 'student';

        return User::query()->whereHas('role', fn ($query) => $query->where('slug', $role))->where(fn ($query) => $query->whereDoesntHave($type === 'teachers' ? 'teacherProfile' : 'studentProfile')->orWhereKey($currentUserId))->orderBy('name')->get(['id', 'name', 'email']);
    }

    private function ensureEligibleUser(string $type, int $userId): void
    {
        $role = $type === 'teachers' ? 'teacher' : 'student';
        abort_unless(User::query()->whereKey($userId)->whereHas('role', fn ($query) => $query->where('slug', $role))->exists(), 422);
    }

    private function rules(string $type, ?Model $profile = null): array
    {
        return $type === 'teachers'
            ? ['user_id' => ['required', 'integer', Rule::unique('teacher_profiles', 'user_id')->ignore($profile)], 'employee_no' => ['nullable', 'string', 'max:50', Rule::unique('teacher_profiles', 'employee_no')->ignore($profile)], 'phone' => ['nullable', 'string', 'max:30'], 'designation' => ['nullable', 'string', 'max:120']]
            : ['user_id' => ['required', 'integer', Rule::unique('student_profiles', 'user_id')->ignore($profile)], 'student_no' => ['nullable', 'string', 'max:50', Rule::unique('student_profiles', 'student_no')->ignore($profile)], 'date_of_birth' => ['nullable', 'date', 'before:today'], 'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')], 'school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')], 'school_section_id' => ['required', 'integer', Rule::exists('school_sections', 'id')]];
    }

    private function ensureStudentSectionMatchesClass(string $type, array $data): void
    {
        if ($type === 'students' && ! SchoolSection::query()->whereKey($data['school_section_id'])->where('school_class_id', $data['school_class_id'])->exists()) {
            throw ValidationException::withMessages(['school_section_id' => 'Choose a section that belongs to the selected class.']);
        }
    }

    private function formData(string $type): array
    {
        return $type === 'teachers' ? [] : ['years' => AcademicYear::query()->orderByDesc('is_current')->orderByDesc('starts_on')->get(['id', 'name']), 'classes' => SchoolClass::query()->with('sections:id,school_class_id,name')->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])];
    }

    private function label(string $type): string
    {
        return $type === 'teachers' ? 'Teacher' : 'Student';
    }

    private function ensureManager(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super-admin', 'admin'), 403);
    }

    private function log(Request $request, string $action, Model $profile, array $properties): void
    {
        DB::table('activity_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'subject_type' => $profile::class, 'subject_id' => $profile->id, 'properties' => json_encode($properties), 'ip_address' => $request->ip(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
