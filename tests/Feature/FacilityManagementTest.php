<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolFacility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FacilityManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_admin_can_publish_a_facility_with_a_photo(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/facilities', [
            'title' => 'Science Laboratory',
            'description' => 'A practical space for student experiments and scientific discovery.',
            'is_published' => true,
            'sort_order' => 1,
            'photo' => UploadedFile::fake()->image('science-laboratory.jpg'),
        ])->assertRedirect('/admin/facilities');

        $facility = SchoolFacility::firstOrFail();
        $this->assertTrue($facility->is_published);
        $this->assertSame(1, $facility->sort_order);
        Storage::disk('public')->assertExists($facility->photo_path);

        SchoolFacility::create(['title' => 'Private transport', 'description' => 'Private planning information.', 'is_published' => false]);
        $this->get('/facilities')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Facilities')
            ->has('facilities', 1)
            ->where('facilities.0.title', 'Science Laboratory'));

        $photoPath = $facility->photo_path;
        $this->actingAs($admin)->delete("/admin/facilities/{$facility->id}")->assertRedirect('/admin/facilities');
        $this->assertDatabaseMissing('school_facilities', ['id' => $facility->id]);
        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_non_admin_cannot_manage_facilities(): void
    {
        $teacherRole = Role::create(['name' => 'Teacher', 'slug' => 'teacher']);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id]);

        $this->actingAs($teacher)->get('/admin/facilities')->assertForbidden();
    }
}
