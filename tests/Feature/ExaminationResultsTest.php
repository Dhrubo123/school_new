<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Examination;
use App\Models\ExamSchedule;
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

class ExaminationResultsTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $slug, string $name): Role
    {
        return Role::create(['name' => $name, 'slug' => $slug]);
    }

    public function test_teacher_enters_marks_admin_publishes_and_linked_portals_see_report(): void
    {
        $admin = User::factory()->create(['role_id' => $this->role('admin', 'Admin')->id]);
        $teacher = User::factory()->create(['role_id' => $this->role('teacher', 'Teacher')->id]);
        $studentUser = User::factory()->create(['name' => 'Nafisa Islam', 'role_id' => $this->role('student', 'Student')->id]);
        $guardian = User::factory()->create(['role_id' => $this->role('guardian', 'Guardian')->id]);
        $year = AcademicYear::create(['name' => '2027', 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'is_current' => true]);
        $class = SchoolClass::create(['name' => 'Class VI', 'code' => 'VI']);
        $section = SchoolSection::create(['school_class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $teacherProfile = TeacherProfile::create(['user_id' => $teacher->id, 'designation' => 'Mathematics Teacher']);
        $student = StudentProfile::create(['user_id' => $studentUser->id, 'student_no' => 'S-101', 'academic_year_id' => $year->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id]);
        $student->guardians()->attach($guardian->id);
        TeachingAssignment::create(['academic_year_id' => $year->id, 'teacher_profile_id' => $teacherProfile->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id]);
        $examination = Examination::create(['academic_year_id' => $year->id, 'title' => 'Half-Yearly Examination 2027', 'type' => 'half_yearly', 'created_by' => $admin->id]);
        $schedule = ExamSchedule::create(['examination_id' => $examination->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id, 'exam_date' => '2027-06-15', 'full_marks' => 100, 'pass_marks' => 33]);

        $this->actingAs($teacher)->get('/teacher/results')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Teacher/Results')->has('roster', 1));
        $this->actingAs($teacher)->post("/teacher/results/{$schedule->id}/marks", ['marks' => [['student_profile_id' => $student->id, 'status' => 'present', 'marks_obtained' => 90, 'remarks' => 'Excellent work']]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('exam_marks', ['exam_schedule_id' => $schedule->id, 'student_profile_id' => $student->id, 'marks_obtained' => 90]);

        $this->actingAs($admin)->patch("/admin/examinations/{$examination->id}/status", ['action' => 'review'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch("/admin/examinations/{$examination->id}/status", ['action' => 'publish'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('examinations', ['id' => $examination->id, 'status' => 'published']);

        $this->actingAs($studentUser)->get("/student/results?exam={$examination->id}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Portal/Results')->where('report.summary.grade', 'A+')->where('report.summary.gpa', 5));
        $this->actingAs($guardian)->get("/guardian/results?student={$student->id}&exam={$examination->id}")->assertOk()->assertInertia(fn (Assert $page) => $page->where('student.id', $student->id)->where('report.summary.grade', 'A+'));
    }

    public function test_admin_cannot_publish_exam_until_all_students_have_marks(): void
    {
        $admin = User::factory()->create(['role_id' => $this->role('admin', 'Admin')->id]);
        $year = AcademicYear::create(['name' => '2027', 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'is_current' => true]);
        $class = SchoolClass::create(['name' => 'Class VI', 'code' => 'VI']);
        $section = SchoolSection::create(['school_class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $studentUser = User::factory()->create(['role_id' => $this->role('student', 'Student')->id]);
        StudentProfile::create(['user_id' => $studentUser->id, 'academic_year_id' => $year->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id]);
        $examination = Examination::create(['academic_year_id' => $year->id, 'title' => 'Monthly Test', 'type' => 'monthly', 'created_by' => $admin->id]);
        ExamSchedule::create(['examination_id' => $examination->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id, 'exam_date' => '2027-02-15', 'full_marks' => 50, 'pass_marks' => 17]);

        $this->actingAs($admin)->patch("/admin/examinations/{$examination->id}/status", ['action' => 'publish'])->assertSessionHasErrors('results');
    }
}
