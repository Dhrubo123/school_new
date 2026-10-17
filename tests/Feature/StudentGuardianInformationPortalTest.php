<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Examination;
use App\Models\ExamSchedule;
use App\Models\FeeInvoice;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolSection;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentGuardianInformationPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_information_is_visible_to_the_correct_student_and_linked_guardian(): void
    {
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $teacherRole = Role::create(['name' => 'Teacher', 'slug' => 'teacher']);
        $studentRole = Role::create(['name' => 'Student', 'slug' => 'student']);
        $guardianRole = Role::create(['name' => 'Guardian', 'slug' => 'guardian']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id]);
        $studentUser = User::factory()->create(['role_id' => $studentRole->id, 'name' => 'Nafisa Islam']);
        $otherStudentUser = User::factory()->create(['role_id' => $studentRole->id, 'name' => 'Other Student']);
        $guardian = User::factory()->create(['role_id' => $guardianRole->id]);
        $year = AcademicYear::create(['name' => '2026–27', 'starts_on' => '2026-01-01', 'ends_on' => '2027-12-31', 'is_current' => true]);
        $class = SchoolClass::create(['name' => 'Class VI', 'code' => 'VI']);
        $sectionA = SchoolSection::create(['school_class_id' => $class->id, 'name' => 'A']);
        $sectionB = SchoolSection::create(['school_class_id' => $class->id, 'name' => 'B']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $student = StudentProfile::create(['user_id' => $studentUser->id, 'student_no' => 'S-101', 'academic_year_id' => $year->id, 'school_class_id' => $class->id, 'school_section_id' => $sectionA->id]);
        $otherStudent = StudentProfile::create(['user_id' => $otherStudentUser->id, 'student_no' => 'S-102', 'academic_year_id' => $year->id, 'school_class_id' => $class->id, 'school_section_id' => $sectionB->id]);
        $student->guardians()->attach($guardian->id);

        $examination = Examination::create(['academic_year_id' => $year->id, 'title' => 'Half-Yearly Examination', 'type' => 'half_yearly', 'routine_instructions' => 'Arrive 30 minutes early.', 'created_by' => $admin->id]);
        $schedule = ExamSchedule::create(['examination_id' => $examination->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id, 'school_section_id' => $sectionA->id, 'exam_date' => now('Asia/Dhaka')->addDays(5)->toDateString(), 'starts_at' => '09:00', 'duration_minutes' => 120, 'room' => 'Main Hall', 'full_marks' => 100, 'pass_marks' => 33]);
        ExamSchedule::create(['examination_id' => $examination->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id, 'school_section_id' => $sectionB->id, 'exam_date' => now('Asia/Dhaka')->addDays(6)->toDateString(), 'starts_at' => '10:00', 'duration_minutes' => 120, 'room' => 'Room 2', 'full_marks' => 100, 'pass_marks' => 33]);

        $this->actingAs($studentUser)->get('/student/exam-routine')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Portal/ExamRoutine')->has('schedules', 0));
        $this->actingAs($admin)->patch("/admin/examinations/{$examination->id}/routine-status", ['action' => 'publish'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('examinations', ['id' => $examination->id, 'status' => 'draft', 'published_at' => null]);
        $this->assertNotNull($examination->fresh()->routine_published_at);

        $teacherProfile = TeacherProfile::create(['user_id' => $teacher->id]);
        $assignment = TeachingAssignment::create(['academic_year_id' => $year->id, 'teacher_profile_id' => $teacherProfile->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id, 'school_section_id' => $sectionA->id]);
        foreach (['present', 'late', 'absent', 'excused'] as $index => $status) {
            $session = AttendanceSession::create(['academic_year_id' => $year->id, 'teaching_assignment_id' => $assignment->id, 'teacher_profile_id' => $teacherProfile->id, 'school_class_id' => $class->id, 'school_section_id' => $sectionA->id, 'attendance_date' => now('Asia/Dhaka')->subDays($index)->toDateString(), 'period_label' => 'Period '.($index + 1), 'taken_by' => $teacher->id]);
            AttendanceRecord::create(['attendance_session_id' => $session->id, 'student_profile_id' => $student->id, 'status' => $status]);
        }

        $admin->notices()->create(['title' => 'Exam preparation', 'slug' => 'exam-preparation', 'summary' => 'Preparation guidance', 'body' => 'Please revise every subject.', 'status' => 'published', 'published_at' => now()->subHour()]);
        $admin->notices()->create(['title' => 'Private draft', 'slug' => 'private-draft', 'summary' => 'Draft', 'body' => 'Not visible', 'status' => 'draft']);
        $admin->events()->create(['title' => 'Science fair', 'slug' => 'science-fair', 'summary' => 'Annual student science fair', 'body' => 'All families are welcome.', 'venue' => 'School field', 'starts_at' => now()->addDays(7), 'status' => 'published', 'published_at' => now()->subHour()]);
        $admin->events()->create(['title' => 'Draft event', 'slug' => 'draft-event', 'summary' => 'Draft', 'body' => 'Not visible', 'venue' => 'Hall', 'starts_at' => now()->addDays(8), 'status' => 'draft']);
        FeeInvoice::create(['student_profile_id' => $student->id, 'academic_year_id' => $year->id, 'invoice_no' => 'INV-PORTAL-1', 'title' => 'Monthly fees', 'issued_on' => now()->toDateString(), 'due_date' => now()->addDays(3)->toDateString(), 'subtotal_amount' => 1000, 'total_amount' => 1000, 'paid_amount' => 0, 'balance_amount' => 1000, 'status' => 'unpaid', 'created_by' => $admin->id]);

        $this->actingAs($studentUser)->get('/student')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Information')
            ->where('student.id', $student->id)
            ->has('examSchedules', 1)
            ->where('examSchedules.0.id', $schedule->id)
            ->where('attendanceSummary.total', 4)
            ->where('attendanceSummary.rate', 66.7)
            ->has('notices', 1)
            ->has('events', 1)
            ->has('reminders', 3)
            ->where('outstandingBalance', 1000)
        );

        $this->actingAs($studentUser)->get('/student/attendance?month='.now('Asia/Dhaka')->format('Y-m'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Portal/Attendance')->has('records', 4)->where('summary.excused', 1));
        $this->actingAs($studentUser)->get('/student/exam-routine')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Portal/ExamRoutine')->has('schedules', 1)->where('schedules.0.room', 'Main Hall'));

        $this->actingAs($guardian)->get("/guardian?student={$student->id}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Portal/Information')->where('student.id', $student->id)->has('examSchedules', 1));
        $this->actingAs($guardian)->get("/guardian?student={$otherStudent->id}")->assertOk()->assertInertia(fn (Assert $page) => $page->where('student.id', $student->id));

        $this->actingAs($admin)->put("/admin/examinations/{$examination->id}/schedules/{$schedule->id}", ['subject_id' => $subject->id, 'school_class_id' => $class->id, 'school_section_id' => $sectionA->id, 'exam_date' => now()->addDays(9)->toDateString(), 'starts_at' => '09:00', 'duration_minutes' => 120, 'room' => 'Changed room', 'full_marks' => 100, 'pass_marks' => 33])->assertSessionHasErrors('examination');
        $this->actingAs($admin)->patch("/admin/examinations/{$examination->id}/routine-status", ['action' => 'unpublish'])->assertSessionHasNoErrors();
        $this->actingAs($studentUser)->get('/student/exam-routine')->assertInertia(fn (Assert $page) => $page->has('schedules', 0));
    }

    public function test_routine_cannot_be_published_without_a_complete_timed_schedule(): void
    {
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $year = AcademicYear::create(['name' => '2026–27', 'starts_on' => '2026-01-01', 'ends_on' => '2027-12-31', 'is_current' => true]);
        $class = SchoolClass::create(['name' => 'Class VI', 'code' => 'VI']);
        $section = SchoolSection::create(['school_class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['name' => 'English', 'code' => 'ENG']);
        $examination = Examination::create(['academic_year_id' => $year->id, 'title' => 'Monthly Test', 'type' => 'monthly', 'created_by' => $admin->id]);

        $this->actingAs($admin)->patch("/admin/examinations/{$examination->id}/routine-status", ['action' => 'publish'])->assertSessionHasErrors('routine');
        ExamSchedule::create(['examination_id' => $examination->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id, 'exam_date' => now()->addWeek()->toDateString(), 'full_marks' => 50, 'pass_marks' => 17]);
        $this->actingAs($admin)->patch("/admin/examinations/{$examination->id}/routine-status", ['action' => 'publish'])->assertSessionHasErrors('routine');
        $this->assertNull($examination->fresh()->routine_published_at);
    }
}
