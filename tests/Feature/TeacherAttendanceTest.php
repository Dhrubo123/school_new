<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
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

class TeacherAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $slug, string $name): Role
    {
        return Role::create(['name' => $name, 'slug' => $slug]);
    }

    public function test_teacher_can_mark_and_correct_attendance_for_an_assigned_section(): void
    {
        $teacherRole = $this->role('teacher', 'Teacher');
        $studentRole = $this->role('student', 'Student');
        $teacher = User::factory()->create(['role_id' => $teacherRole->id]);
        $studentOne = User::factory()->create(['role_id' => $studentRole->id]);
        $studentTwo = User::factory()->create(['role_id' => $studentRole->id]);
        $year = AcademicYear::create(['name' => '2027', 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'is_current' => true]);
        $class = SchoolClass::create(['name' => 'Class VI', 'code' => 'VI']);
        $section = SchoolSection::create(['school_class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $teacherProfile = TeacherProfile::create(['user_id' => $teacher->id]);
        $firstProfile = StudentProfile::create(['user_id' => $studentOne->id, 'student_no' => 'S-001', 'academic_year_id' => $year->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id]);
        $secondProfile = StudentProfile::create(['user_id' => $studentTwo->id, 'student_no' => 'S-002', 'academic_year_id' => $year->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id]);
        $assignment = TeachingAssignment::create(['academic_year_id' => $year->id, 'teacher_profile_id' => $teacherProfile->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id]);
        $payload = ['teaching_assignment_id' => $assignment->id, 'school_section_id' => $section->id, 'attendance_date' => now()->toDateString(), 'period_label' => 'Period 1', 'records' => [['student_profile_id' => $firstProfile->id, 'status' => 'present', 'note' => ''], ['student_profile_id' => $secondProfile->id, 'status' => 'late', 'note' => 'Arrived after assembly']]];

        $this->actingAs($teacher)->get('/teacher/attendance')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Teacher/Attendance')->has('roster', 2));
        $this->actingAs($teacher)->post('/teacher/attendance', $payload)->assertSessionHasNoErrors();
        $session = AttendanceSession::firstOrFail();
        $this->assertDatabaseHas('attendance_records', ['attendance_session_id' => $session->id, 'student_profile_id' => $secondProfile->id, 'status' => 'late']);

        $correction = $payload;
        $correction['records'] = [['student_profile_id' => $firstProfile->id, 'status' => 'absent', 'note' => 'Sick leave'], ['student_profile_id' => $secondProfile->id, 'status' => 'present', 'note' => '']];
        $this->actingAs($teacher)->post('/teacher/attendance', $correction)->assertSessionHasNoErrors();
        $this->assertSame(1, AttendanceSession::count());
        $this->assertDatabaseHas('attendance_records', ['attendance_session_id' => $session->id, 'student_profile_id' => $firstProfile->id, 'status' => 'absent']);
        $this->assertSame(2, AttendanceRecord::count());
    }

    public function test_teacher_cannot_submit_attendance_for_another_teachers_assignment(): void
    {
        $teacherRole = $this->role('teacher', 'Teacher');
        $studentRole = $this->role('student', 'Student');
        $firstTeacher = User::factory()->create(['role_id' => $teacherRole->id]);
        $secondTeacher = User::factory()->create(['role_id' => $teacherRole->id]);
        $year = AcademicYear::create(['name' => '2027', 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'is_current' => true]);
        $class = SchoolClass::create(['name' => 'Class VI', 'code' => 'VI']);
        $section = SchoolSection::create(['school_class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $assignment = TeachingAssignment::create(['academic_year_id' => $year->id, 'teacher_profile_id' => TeacherProfile::create(['user_id' => $secondTeacher->id])->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id]);
        TeacherProfile::create(['user_id' => $firstTeacher->id]);
        $student = User::factory()->create(['role_id' => $studentRole->id]);
        $studentProfile = StudentProfile::create(['user_id' => $student->id, 'academic_year_id' => $year->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id]);

        $this->actingAs($firstTeacher)->post('/teacher/attendance', ['teaching_assignment_id' => $assignment->id, 'school_section_id' => $section->id, 'attendance_date' => now()->toDateString(), 'period_label' => 'Period 1', 'records' => [['student_profile_id' => $studentProfile->id, 'status' => 'present']]])->assertNotFound();
    }
}
