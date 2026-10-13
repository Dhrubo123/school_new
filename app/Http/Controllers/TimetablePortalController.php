<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimetablePortalController extends Controller
{
    public function student(Request $request): Response
    {
        $student = $request->user()?->studentProfile?->load('user:id,name', 'academicYear:id,name', 'schoolClass:id,name', 'schoolSection:id,name');
        abort_unless($student, 403);

        return $this->forStudent('student', $student, collect());
    }

    public function guardian(Request $request): Response
    {
        $students = $request->user()?->linkedStudents()->with(['user:id,name', 'academicYear:id,name', 'schoolClass:id,name', 'schoolSection:id,name'])->get() ?? collect();
        $student = $students->firstWhere('id', (int) $request->query('student')) ?? $students->first();
        abort_unless($student, 403);

        return $this->forStudent('guardian', $student, $students);
    }

    public function teacher(Request $request): Response
    {
        $teacher = $request->user()?->teacherProfile;
        abort_unless($teacher, 403);

        return Inertia::render('Portal/Timetable', [
            'role' => 'teacher',
            'title' => 'My teaching routine',
            'subtitle' => 'Your published periods across all assigned classes.',
            'timetables' => Timetable::query()
                ->with(['academicYear:id,name', 'schoolClass:id,name', 'schoolSection:id,name', 'subject:id,name,code'])
                ->where('teacher_profile_id', $teacher->id)
                ->where('is_published', true)
                ->weeklyOrder()
                ->get(),
        ]);
    }

    private function forStudent(string $role, StudentProfile $student, $students): Response
    {
        return Inertia::render('Portal/Timetable', [
            'role' => $role,
            'title' => $role === 'guardian' ? "{$student->user?->name}'s class routine" : 'My class routine',
            'subtitle' => "{$student->academicYear?->name} · {$student->schoolClass?->name} · Section {$student->schoolSection?->name}",
            'student' => $student,
            'students' => collect($students)->map(fn (StudentProfile $item) => [
                'id' => $item->id,
                'name' => $item->user?->name,
                'class_name' => $item->schoolClass?->name,
                'section_name' => $item->schoolSection?->name,
            ])->values(),
            'timetables' => Timetable::query()
                ->with(['subject:id,name,code', 'teacherProfile.user:id,name'])
                ->where('academic_year_id', $student->academic_year_id)
                ->where('school_class_id', $student->school_class_id)
                ->where('school_section_id', $student->school_section_id)
                ->where('is_published', true)
                ->weeklyOrder()
                ->get(),
        ]);
    }
}
