<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolSection;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_class_routine_and_students_only_see_their_published_section(): void
    {
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $teacherRole = Role::create(['name' => 'Teacher', 'slug' => 'teacher']);
        $studentRole = Role::create(['name' => 'Student', 'slug' => 'student']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id]);
        $studentUser = User::factory()->create(['role_id' => $studentRole->id]);
        $year = AcademicYear::create(['name' => '2027', 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'is_current' => true]);
        $class = SchoolClass::create(['name' => 'Class VI', 'code' => 'VI']);
        $sectionA = SchoolSection::create(['school_class_id' => $class->id, 'name' => 'A']);
        $sectionB = SchoolSection::create(['school_class_id' => $class->id, 'name' => 'B']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $teacherProfile = TeacherProfile::create(['user_id' => $teacher->id]);
        StudentProfile::create(['user_id' => $studentUser->id, 'academic_year_id' => $year->id, 'school_class_id' => $class->id, 'school_section_id' => $sectionA->id]);

        $payload = [
            'academic_year_id' => $year->id,
            'school_class_id' => $class->id,
            'school_section_id' => $sectionA->id,
            'subject_id' => $subject->id,
            'teacher_profile_id' => $teacherProfile->id,
            'day_of_week' => 'monday',
            'starts_at' => '08:00',
            'ends_at' => '08:45',
            'room' => 'Room 204',
            'is_published' => true,
        ];

        $this->actingAs($admin)->post('/admin/timetables', $payload)->assertRedirect('/admin/timetables')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('timetables', ['school_section_id' => $sectionA->id, 'subject_id' => $subject->id, 'day_of_week' => 'monday']);

        $conflict = [...$payload, 'subject_id' => Subject::create(['name' => 'English', 'code' => 'ENG'])->id, 'starts_at' => '08:30', 'ends_at' => '09:15'];
        $this->actingAs($admin)->post('/admin/timetables', $conflict)->assertSessionHasErrors('starts_at');

        Timetable::create([...$payload, 'school_section_id' => $sectionB->id, 'teacher_profile_id' => null, 'starts_at' => '10:00', 'ends_at' => '10:45']);
        $this->actingAs($studentUser)->get('/student/timetable')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Timetable')
            ->has('timetables', 1)
            ->where('timetables.0.school_section_id', $sectionA->id)
        );
    }
}
