<?php

namespace App\Http\Controllers;

use App\Http\Requests\Teacher\StoreAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\SchoolSection;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\TeachingAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TeacherAttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $profile = $request->user()->teacherProfile;
        $assignments = $profile ? TeachingAssignment::query()->where('teacher_profile_id', $profile->id)->with(['academicYear:id,name', 'subject:id,name,code', 'schoolClass:id,name', 'schoolSection:id,name'])->orderByDesc('academic_year_id')->get() : collect();
        $assignmentId = (int) $request->query('assignment_id', $assignments->first()?->id);
        $assignment = $assignments->firstWhere('id', $assignmentId);
        $sections = $assignment ? ($assignment->school_section_id ? SchoolSection::query()->whereKey($assignment->school_section_id)->get(['id', 'name', 'school_class_id']) : SchoolSection::query()->where('school_class_id', $assignment->school_class_id)->orderBy('name')->get(['id', 'name', 'school_class_id'])) : collect();
        $sectionId = (int) $request->query('school_section_id', $sections->first()?->id);
        $date = (string) $request->query('attendance_date', now('Asia/Dhaka')->toDateString());
        $period = trim((string) $request->query('period_label', 'Period 1')) ?: 'Period 1';
        $session = $assignment && $sections->contains('id', $sectionId) ? AttendanceSession::query()->where('teaching_assignment_id', $assignment->id)->where('school_section_id', $sectionId)->whereDate('attendance_date', $date)->where('period_label', $period)->with('records:id,attendance_session_id,student_profile_id,status,note')->first() : null;
        $stored = $session?->records->keyBy('student_profile_id') ?? collect();
        $roster = $assignment && $sections->contains('id', $sectionId) ? StudentProfile::query()->where('academic_year_id', $assignment->academic_year_id)->where('school_class_id', $assignment->school_class_id)->where('school_section_id', $sectionId)->with('user:id,name,email')->orderBy('student_no')->get()->map(fn (StudentProfile $student) => ['id' => $student->id, 'student_no' => $student->student_no, 'name' => $student->user->name, 'status' => $stored->get($student->id)?->status ?? 'present', 'note' => $stored->get($student->id)?->note ?? '']) : collect();

        return Inertia::render('Teacher/Attendance', [
            'assignments' => $assignments,
            'selectedAssignmentId' => $assignment?->id,
            'sections' => $sections,
            'selectedSectionId' => $sectionId ?: null,
            'attendanceDate' => $date,
            'periodLabel' => $session?->period_label ?? $period,
            'roster' => $roster,
            'hasExistingRecord' => $session !== null,
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $profile = $request->user()->teacherProfile;
        abort_unless($profile instanceof TeacherProfile, 403);
        $data = $request->validated();
        $assignment = TeachingAssignment::query()->whereKey($data['teaching_assignment_id'])->where('teacher_profile_id', $profile->id)->firstOrFail();
        $this->ensureSectionIsAllowed($assignment, (int) $data['school_section_id']);
        $rosterIds = StudentProfile::query()->where('academic_year_id', $assignment->academic_year_id)->where('school_class_id', $assignment->school_class_id)->where('school_section_id', $data['school_section_id'])->pluck('id')->sort()->values();
        $submittedIds = collect($data['records'])->pluck('student_profile_id')->map(fn ($id) => (int) $id)->sort()->values();
        if ($rosterIds->isEmpty()) {
            throw ValidationException::withMessages(['records' => 'There are no enrolled students in this class section.']);
        }
        if ($rosterIds->all() !== $submittedIds->all()) {
            throw ValidationException::withMessages(['records' => 'Attendance must include every student in the selected class section.']);
        }

        DB::transaction(function () use ($request, $profile, $assignment, $data) {
            $session = AttendanceSession::query()->where('teaching_assignment_id', $assignment->id)->where('school_section_id', $data['school_section_id'])->whereDate('attendance_date', $data['attendance_date'])->where('period_label', $data['period_label'])->first();
            $session ??= new AttendanceSession([
                'teaching_assignment_id' => $assignment->id,
                'school_section_id' => $data['school_section_id'],
                'attendance_date' => $data['attendance_date'],
                'period_label' => $data['period_label'],
            ]);
            $session->fill([
                'academic_year_id' => $assignment->academic_year_id,
                'teacher_profile_id' => $profile->id,
                'school_class_id' => $assignment->school_class_id,
                'taken_by' => $request->user()->id,
            ]);
            $session->save();
            foreach ($data['records'] as $record) {
                $storedRecord = AttendanceRecord::query()->firstOrNew(['attendance_session_id' => $session->id, 'student_profile_id' => $record['student_profile_id']]);
                $storedRecord->status = $record['status'];
                $storedRecord->note = $record['note'] ?: null;
                $storedRecord->save();
            }
            DB::table('activity_logs')->insert(['user_id' => $request->user()->id, 'action' => 'attendance.saved', 'subject_type' => AttendanceSession::class, 'subject_id' => $session->id, 'properties' => json_encode(['record_count' => count($data['records'])]), 'ip_address' => $request->ip(), 'created_at' => now(), 'updated_at' => now()]);
        });

        return back()->with('success', 'Attendance saved. You can reopen this record later to make corrections.');
    }

    private function ensureSectionIsAllowed(TeachingAssignment $assignment, int $sectionId): void
    {
        $allowed = SchoolSection::query()->whereKey($sectionId)->where('school_class_id', $assignment->school_class_id)->when($assignment->school_section_id, fn ($query) => $query->whereKey($assignment->school_section_id))->exists();
        if (! $allowed) {
            throw ValidationException::withMessages(['school_section_id' => 'Choose a section included in your teaching assignment.']);
        }
    }
}
