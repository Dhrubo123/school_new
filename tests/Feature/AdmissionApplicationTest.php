<?php

namespace Tests\Feature;

use App\Models\AdmissionApplication;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_can_submit_a_valid_admission_enquiry(): void
    {
        $this->post('/admissions/apply', ['student_name' => 'Maliha Akter', 'guardian_name' => 'Rafiq Ahmed', 'email' => 'rafiq@example.com', 'phone' => '01711000000', 'applying_for_class' => 'Class VI', 'message' => 'We would like to arrange a visit.'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('admission_applications', ['student_name' => 'Maliha Akter', 'status' => 'new']);
    }

    public function test_invalid_mobile_number_is_rejected(): void
    {
        $this->post('/admissions/apply', ['student_name' => 'Maliha Akter', 'guardian_name' => 'Rafiq Ahmed', 'phone' => '123', 'applying_for_class' => 'Class VI'])->assertSessionHasErrors('phone');
    }

    public function test_admin_can_review_and_update_an_admission_application(): void
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $application = AdmissionApplication::create([
            'application_no' => 'SR-261012-ABCDE',
            'student_name' => 'Maliha Akter',
            'guardian_name' => 'Rafiq Ahmed',
            'phone' => '01711000000',
            'applying_for_class' => 'Class VI',
        ]);

        $this->actingAs($admin)->get('/admin/admissions')->assertOk();
        $this->actingAs($admin)->get("/admin/admissions/{$application->id}")->assertOk();
        $this->actingAs($admin)->put("/admin/admissions/{$application->id}", [
            'status' => 'contacted',
            'review_notes' => 'Called the guardian and arranged a campus visit.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('admission_applications', [
            'id' => $application->id,
            'status' => 'contacted',
            'review_notes' => 'Called the guardian and arranged a campus visit.',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_non_admin_cannot_review_admission_applications(): void
    {
        $application = AdmissionApplication::create([
            'application_no' => 'SR-261012-FGHIJ',
            'student_name' => 'Maliha Akter',
            'guardian_name' => 'Rafiq Ahmed',
            'phone' => '01711000000',
            'applying_for_class' => 'Class VI',
        ]);

        $this->actingAs(User::factory()->create())->get("/admin/admissions/{$application->id}")->assertForbidden();
    }
}
