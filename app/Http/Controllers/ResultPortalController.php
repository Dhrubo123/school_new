<?php

namespace App\Http\Controllers;

use App\Models\Examination;
use App\Models\StudentProfile;
use App\Services\ResultCalculator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResultPortalController extends Controller
{
    public function student(Request $request, ResultCalculator $calculator): Response
    {
        $student = $request->user()?->studentProfile?->load('user:id,name', 'academicYear:id,name', 'schoolClass:id,name', 'schoolSection:id,name');
        abort_unless($student, 403);

        return $this->renderPortal('student', $student, [], $request, $calculator);
    }

    public function guardian(Request $request, ResultCalculator $calculator): Response
    {
        $students = $request->user()?->linkedStudents()
            ->with(['user:id,name', 'academicYear:id,name', 'schoolClass:id,name', 'schoolSection:id,name'])
            ->get() ?? collect();
        $student = $students->firstWhere('id', (int) $request->query('student')) ?? $students->first();
        abort_unless($student, 403);

        return $this->renderPortal('guardian', $student, $students, $request, $calculator);
    }

    private function renderPortal(string $role, StudentProfile $student, $students, Request $request, ResultCalculator $calculator): Response
    {
        $students = collect($students);
        $examinations = Examination::query()
            ->where('status', 'published')
            ->where('academic_year_id', $student->academic_year_id)
            ->whereHas('schedules', fn ($query) => $query->where('school_class_id', $student->school_class_id)->where('school_section_id', $student->school_section_id))
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'type', 'published_at']);
        $examination = $examinations->firstWhere('id', (int) $request->query('exam')) ?? $examinations->first();

        return Inertia::render('Portal/Results', [
            'role' => $role,
            'student' => $student,
            'students' => $students->map(fn (StudentProfile $item) => ['id' => $item->id, 'name' => $item->user?->name, 'class_name' => $item->schoolClass?->name, 'section_name' => $item->schoolSection?->name])->values(),
            'examinations' => $examinations,
            'selectedExam' => $examination,
            'report' => $examination ? $calculator->report($student, $examination) : null,
        ]);
    }
}
