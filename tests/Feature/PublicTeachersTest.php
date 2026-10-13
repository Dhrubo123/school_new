<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicTeachersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_a_teacher_profile_with_a_photo(): void
    {
        Storage::fake('public');
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $teacherRole = Role::create(['name' => 'Teacher', 'slug' => 'teacher']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $teacher = User::factory()->create(['name' => 'Amina Rahman', 'role_id' => $teacherRole->id]);

        $this->actingAs($admin)->post('/admin/teachers', [
            'user_id' => $teacher->id,
            'employee_no' => 'T-100',
            'designation' => 'Senior Science Teacher',
            'bio' => 'Amina helps learners build confidence through practical science.',
            'is_public' => true,
            'public_sort_order' => 1,
            'photo' => UploadedFile::fake()->image('amina.jpg'),
        ])->assertRedirect('/admin/teachers');

        $profile = TeacherProfile::firstOrFail();
        $this->assertTrue($profile->is_public);
        $this->assertSame('Senior Science Teacher', $profile->designation);
        Storage::disk('public')->assertExists($profile->photo_path);
    }

    public function test_public_teachers_page_only_displays_published_teacher_profiles(): void
    {
        $publicTeacher = User::factory()->create(['name' => 'Sadia Karim']);
        $privateTeacher = User::factory()->create(['name' => 'Private Teacher']);
        TeacherProfile::create(['user_id' => $publicTeacher->id, 'designation' => 'English Teacher', 'bio' => 'Published biography.', 'is_public' => true, 'public_sort_order' => 1]);
        TeacherProfile::create(['user_id' => $privateTeacher->id, 'designation' => 'Private Teacher', 'is_public' => false]);

        $this->get('/teachers')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Teachers')
            ->has('teachers', 1)
            ->where('teachers.0.user.name', 'Sadia Karim')
            ->where('teachers.0.designation', 'English Teacher'));
    }
}
