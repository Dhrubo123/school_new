<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolSection;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreAcademicDataTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $slug, string $name): Role
    {
        return Role::create(['name' => $name, 'slug' => $slug]);
    }

    public function test_admin_can_build_academic_structure_and_assign_people(): void
    {
        $adminRole = $this->role('admin', 'Admin');
        $teacherRole = $this->role('teacher', 'Teacher');
        $studentRole = $this->role('student', 'Student');
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id]);
        $student = User::factory()->create(['role_id' => $studentRole->id]);

        $this->actingAs($admin)->get('/admin/teachers/create')->assertOk();

        $this->actingAs($admin)->post('/admin/academics/years', ['name' => '2027', 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'is_current' => true])->assertRedirect('/admin/academics?tab=years');
        $this->actingAs($admin)->post('/admin/academics/classes', ['name' => 'Class VI', 'code' => 'VI', 'sort_order' => 6])->assertRedirect('/admin/academics?tab=classes');
        $class = SchoolClass::firstOrFail();
        $this->actingAs($admin)->post('/admin/academics/sections', ['school_class_id' => $class->id, 'name' => 'A', 'capacity' => 35])->assertRedirect('/admin/academics?tab=sections');
        $this->actingAs($admin)->post('/admin/academics/subjects', ['name' => 'Mathematics', 'code' => 'MATH', 'sort_order' => 1])->assertRedirect('/admin/academics?tab=subjects');

        $year = AcademicYear::firstOrFail();
        $section = SchoolSection::firstOrFail();
        $subject = Subject::firstOrFail();
        $this->actingAs($admin)->post('/admin/teachers', ['user_id' => $teacher->id, 'employee_no' => 'T-001', 'phone' => '01711000000', 'designation' => 'Assistant Teacher'])->assertRedirect('/admin/teachers');
        $this->actingAs($admin)->post('/admin/students', ['user_id' => $student->id, 'student_no' => 'S-001', 'date_of_birth' => '2015-02-10', 'academic_year_id' => $year->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id])->assertRedirect('/admin/students');
        $this->actingAs($admin)->post('/admin/teaching-assignments', ['academic_year_id' => $year->id, 'teacher_profile_id' => TeacherProfile::firstOrFail()->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id])->assertRedirect('/admin/teaching-assignments');

        $this->assertDatabaseHas('student_profiles', ['user_id' => $student->id, 'school_section_id' => $section->id]);
        $this->assertDatabaseHas('teaching_assignments', ['subject_id' => $subject->id, 'teacher_profile_id' => TeacherProfile::firstOrFail()->id]);
    }

    public function test_student_section_must_belong_to_the_selected_class(): void
    {
        $adminRole = $this->role('admin', 'Admin');
        $studentRole = $this->role('student', 'Student');
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $student = User::factory()->create(['role_id' => $studentRole->id]);
        $year = AcademicYear::create(['name' => '2027', 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'is_current' => true]);
        $classOne = SchoolClass::create(['name' => 'Class I', 'code' => 'I']);
        $classTwo = SchoolClass::create(['name' => 'Class II', 'code' => 'II']);
        $section = SchoolSection::create(['school_class_id' => $classTwo->id, 'name' => 'A']);

        $this->actingAs($admin)->post('/admin/students', ['user_id' => $student->id, 'academic_year_id' => $year->id, 'school_class_id' => $classOne->id, 'school_section_id' => $section->id])->assertSessionHasErrors('school_section_id');
    }

    public function test_non_admin_cannot_manage_academic_data(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/academics')->assertForbidden();
    }
}
