<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ExamSchedule;
use App\Models\Notice;
use App\Models\SchoolEvent;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class InformationPortalController extends Controller
{
    public function student(Request $request): Response
    {
        $student = $request->user()?->studentProfile?->load($this->studentRelations());
        abort_unless($student, 403);

        return $this->dashboard('student', $student, collect());
    }

    public function guardian(Request $request): Response
    {
        [$student, $students] = $this->guardianStudents($request);

        return $this->dashboard('guardian', $student, $students);
    }

    public function studentExamRoutine(Request $request): Response
    {
        $student = $request->user()?->studentProfile?->load($this->studentRelations());
        abort_unless($student, 403);

        return $this->examRoutine('student', $student, collect());
    }

    public function guardianExamRoutine(Request $request): Response
    {
        [$student, $students] = $this->guardianStudents($request);

        return $this->examRoutine('guardian', $student, $students);
    }

    public function studentAttendance(Request $request): Response
    {
        $student = $request->user()?->studentProfile?->load($this->studentRelations());
        abort_unless($student, 403);

        return $this->attendancePage('student', $student, collect(), $request);
    }

    public function guardianAttendance(Request $request): Response
    {
        [$student, $students] = $this->guardianStudents($request);

        return $this->attendancePage('guardian', $student, $students, $request);
    }

    private function dashboard(string $role, StudentProfile $student, Collection $students): Response
    {
        $attendance = $this->attendance($student);
        $examSchedules = $this->routineSchedules($student)->whereDate('exam_date', '>=', today('Asia/Dhaka'))->limit(6)->get();
        $notices = Notice::published()->latest('published_at')->limit(6)->get(['id', 'title', 'summary', 'published_at']);
        $events = SchoolEvent::published()
            ->where(fn ($query) => $query->whereDate('starts_at', '>=', today('Asia/Dhaka'))->orWhereDate('ends_at', '>=', today('Asia/Dhaka')))
            ->orderBy('starts_at')
            ->limit(6)
            ->get(['id', 'title', 'summary', 'venue', 'starts_at', 'ends_at']);
        $outstandingInvoices = $student->feeInvoices()
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('balance_amount', '>', 0)
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get(['id', 'invoice_no', 'title', 'due_date', 'balance_amount', 'status']);

        return Inertia::render('Portal/Information', [
            'role' => $role,
            'student' => $student,
            'students' => $this->studentsPayload($students),
            'attendanceSummary' => $attendance['summary'],
            'recentAttendance' => $attendance['records'],
            'examSchedules' => $examSchedules,
            'notices' => $notices,
            'events' => $events,
            'outstandingBalance' => (float) $outstandingInvoices->sum('balance_amount'),
            'reminders' => $this->reminders($role, $student, $examSchedules, $events, $outstandingInvoices),
        ]);
    }

    private function examRoutine(string $role, StudentProfile $student, Collection $students): Response
    {
        return Inertia::render('Portal/ExamRoutine', [
            'role' => $role,
            'student' => $student,
            'students' => $this->studentsPayload($students),
            'schedules' => $this->routineSchedules($student)->get(),
        ]);
    }

    private function attendancePage(string $role, StudentProfile $student, Collection $students, Request $request): Response
    {
        $filters = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = $filters['month'] ?? now('Asia/Dhaka')->format('Y-m');
        $attendance = $this->attendance($student, $month, null);

        return Inertia::render('Portal/Attendance', [
            'role' => $role,
            'student' => $student,
            'students' => $this->studentsPayload($students),
            'month' => $month,
            'summary' => $attendance['summary'],
            'records' => $attendance['records'],
        ]);
    }

    private function routineSchedules(StudentProfile $student)
    {
        return ExamSchedule::query()
            ->with(['examination:id,academic_year_id,title,type,starts_on,ends_on,routine_instructions,routine_published_at', 'subject:id,name,code'])
            ->where('school_class_id', $student->school_class_id)
            ->where('school_section_id', $student->school_section_id)
            ->whereHas('examination', fn ($query) => $query
                ->where('academic_year_id', $student->academic_year_id)
                ->whereNotNull('routine_published_at')
                ->where('routine_published_at', '<=', now()))
            ->orderBy('exam_date')
            ->orderBy('starts_at');
    }

    private function attendance(StudentProfile $student, ?string $month = null, ?int $limit = 12): array
    {
        [$year, $monthNumber] = $month ? array_map('intval', explode('-', $month)) : [null, null];
        $base = AttendanceRecord::query()
            ->where('student_profile_id', $student->id)
            ->whereHas('attendanceSession', fn ($query) => $query
                ->where('academic_year_id', $student->academic_year_id)
                ->when($month, fn ($monthQuery) => $monthQuery->whereYear('attendance_date', $year)->whereMonth('attendance_date', $monthNumber)));
        $counts = (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $counts->sum();
        $present = (int) ($counts['present'] ?? 0);
        $late = (int) ($counts['late'] ?? 0);
        $absent = (int) ($counts['absent'] ?? 0);
        $rateDenominator = $present + $late + $absent;

        return [
            'summary' => [
                'total' => $total,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'excused' => (int) ($counts['excused'] ?? 0),
                'rate' => $rateDenominator > 0 ? round((($present + $late) / $rateDenominator) * 100, 1) : null,
            ],
            'records' => (clone $base)
                ->select('attendance_records.*')
                ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
                ->with(['attendanceSession.teachingAssignment.subject:id,name,code'])
                ->orderByDesc('attendance_sessions.attendance_date')
                ->orderByDesc('attendance_records.id')
                ->when($limit, fn ($query) => $query->limit($limit))
                ->get(),
        ];
    }

    private function reminders(string $role, StudentProfile $student, Collection $examSchedules, Collection $events, Collection $invoices): Collection
    {
        $studentQuery = $role === 'guardian' ? '?student='.$student->id : '';
        $reminders = collect();

        foreach ($invoices as $invoice) {
            $overdue = $invoice->due_date && $invoice->due_date->isBefore(today('Asia/Dhaka'));
            $reminders->push([
                'type' => 'fee',
                'title' => $overdue ? 'Overdue fee payment' : 'Fee payment due',
                'detail' => $invoice->title.' · ৳'.number_format((float) $invoice->balance_amount, 2),
                'date' => $invoice->due_date?->toDateString(),
                'priority' => $overdue ? 0 : 2,
                'href' => "/{$role}/fees{$studentQuery}",
            ]);
        }

        foreach ($examSchedules as $schedule) {
            $reminders->push([
                'type' => 'exam',
                'title' => $schedule->subject?->name.' exam',
                'detail' => $schedule->examination?->title.($schedule->starts_at ? ' · '.substr($schedule->starts_at, 0, 5) : ''),
                'date' => $schedule->exam_date?->toDateString(),
                'priority' => 1,
                'href' => "/{$role}/exam-routine{$studentQuery}",
            ]);
        }

        foreach ($events as $event) {
            $reminders->push([
                'type' => 'event',
                'title' => $event->title,
                'detail' => $event->venue,
                'date' => $event->starts_at?->toDateString(),
                'priority' => 3,
                'href' => '/news-events',
            ]);
        }

        return $reminders->sortBy(fn ($item) => sprintf('%02d-%s', $item['priority'], $item['date'] ?? '9999-12-31'))->take(10)->values();
    }

    private function guardianStudents(Request $request): array
    {
        $students = $request->user()?->linkedStudents()->with($this->studentRelations())->get() ?? collect();
        $student = $students->firstWhere('id', (int) $request->query('student')) ?? $students->first();
        abort_unless($student, 403);

        return [$student, $students];
    }

    private function studentsPayload(Collection $students): Collection
    {
        return $students->map(fn (StudentProfile $student) => [
            'id' => $student->id,
            'name' => $student->user?->name,
            'class_name' => $student->schoolClass?->name,
            'section_name' => $student->schoolSection?->name,
        ])->values();
    }

    private function studentRelations(): array
    {
        return ['user:id,name,email', 'academicYear:id,name', 'schoolClass:id,name', 'schoolSection:id,name'];
    }
}
