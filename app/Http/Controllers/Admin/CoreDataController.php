<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\SchoolSection;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CoreDataController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureManager($request);
        $tab = $request->query('tab', 'years');
        abort_unless(array_key_exists($tab, $this->resources()), 404);

        return Inertia::render('Admin/Academics/Index', [
            'tab' => $tab,
            'years' => AcademicYear::query()->orderByDesc('is_current')->orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::query()->with('sections')->orderBy('sort_order')->orderBy('name')->get(),
            'subjects' => Subject::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request, string $type): Response
    {
        $this->ensureManager($request);
        $this->resource($type);

        return Inertia::render('Admin/Academics/Form', ['type' => $type, 'classes' => SchoolClass::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code'])]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->ensureManager($request);
        $model = $this->resource($type);
        $data = $request->validate($this->rules($type));

        $record = DB::transaction(function () use ($request, $type, $model, $data) {
            if ($type === 'years' && $data['is_current']) {
                AcademicYear::query()->update(['is_current' => false]);
            }
            $record = $model::create($data);
            $this->log($request, 'academic-data.created', $record, ['type' => $type]);

            return $record;
        });

        return to_route('admin.academics.index', ['tab' => $type])->with('success', "{$this->label($type)} created.");
    }

    public function edit(Request $request, string $type, int $id): Response
    {
        $this->ensureManager($request);
        $record = $this->find($type, $id);

        return Inertia::render('Admin/Academics/Form', ['type' => $type, 'record' => $record, 'classes' => SchoolClass::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code'])]);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $this->ensureManager($request);
        $record = $this->find($type, $id);
        $data = $request->validate($this->rules($type, $record));

        DB::transaction(function () use ($request, $type, $record, $data) {
            if ($type === 'years' && $data['is_current']) {
                AcademicYear::query()->whereKeyNot($record->id)->update(['is_current' => false]);
            }
            $record->update($data);
            $this->log($request, 'academic-data.updated', $record, ['type' => $type]);
        });

        return to_route('admin.academics.index', ['tab' => $type])->with('success', "{$this->label($type)} saved.");
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        $this->ensureManager($request);
        $record = $this->find($type, $id);
        if (($type === 'classes' && ($record->sections()->exists() || $record->students()->exists() || $record->teachingAssignments()->exists())) || ($type === 'years' && ($record->students()->exists() || $record->teachingAssignments()->exists()))) {
            throw ValidationException::withMessages(['record' => "This {$this->label($type)} is in use and cannot be deleted."]);
        }
        if ($type === 'subjects' && $record->teachingAssignments()->exists()) {
            throw ValidationException::withMessages(['record' => 'This subject is already assigned to a teacher and cannot be deleted.']);
        }
        if ($type === 'sections' && ($record->students()->exists() || $record->teachingAssignments()->exists())) {
            throw ValidationException::withMessages(['record' => 'This section is in use and cannot be deleted.']);
        }

        DB::transaction(function () use ($request, $type, $record) {
            $this->log($request, 'academic-data.deleted', $record, ['type' => $type]);
            $record->delete();
        });

        return to_route('admin.academics.index', ['tab' => $type])->with('success', "{$this->label($type)} deleted.");
    }

    private function resources(): array
    {
        return ['years' => AcademicYear::class, 'classes' => SchoolClass::class, 'sections' => SchoolSection::class, 'subjects' => Subject::class];
    }

    private function resource(string $type): string
    {
        abort_unless(array_key_exists($type, $this->resources()), 404);

        return $this->resources()[$type];
    }

    private function find(string $type, int $id): Model
    {
        return $this->resource($type)::query()->findOrFail($id);
    }

    private function rules(string $type, ?Model $record = null): array
    {
        return match ($type) {
            'years' => ['name' => ['required', 'string', 'max:40', Rule::unique('academic_years', 'name')->ignore($record)], 'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after:starts_on'], 'is_current' => ['required', 'boolean']],
            'classes' => ['name' => ['required', 'string', 'max:80'], 'code' => ['required', 'string', 'max:30', Rule::unique('school_classes', 'code')->ignore($record)], 'sort_order' => ['required', 'integer', 'min:0', 'max:1000']],
            'sections' => ['school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')], 'name' => ['required', 'string', 'max:40', Rule::unique('school_sections', 'name')->where('school_class_id', $this->requestClassId())->ignore($record)], 'capacity' => ['nullable', 'integer', 'min:1', 'max:1000']],
            'subjects' => ['name' => ['required', 'string', 'max:120'], 'code' => ['required', 'string', 'max:30', Rule::unique('subjects', 'code')->ignore($record)], 'sort_order' => ['required', 'integer', 'min:0', 'max:1000']],
        };
    }

    private function requestClassId(): mixed
    {
        return request()->input('school_class_id');
    }

    private function label(string $type): string
    {
        return ['years' => 'Academic year', 'classes' => 'Class', 'sections' => 'Section', 'subjects' => 'Subject'][$type];
    }

    private function ensureManager(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super-admin', 'admin'), 403);
    }

    private function log(Request $request, string $action, Model $record, array $properties): void
    {
        DB::table('activity_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'subject_type' => $record::class, 'subject_id' => $record->id, 'properties' => json_encode($properties), 'ip_address' => $request->ip(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
