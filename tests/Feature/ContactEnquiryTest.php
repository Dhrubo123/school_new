<?php

namespace Tests\Feature;

use App\Models\ContactEnquiry;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactEnquiryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_visitor_can_send_a_contact_enquiry(): void
    {
        $this->get('/contact')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Public/Contact'));
        $this->post('/contact/enquiries', [
            'name' => 'Sadia Rahman',
            'email' => 'sadia@example.test',
            'phone' => '01711000000',
            'subject' => 'Campus visit',
            'message' => 'May we arrange a campus visit next week?',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('contact_enquiries', ['name' => 'Sadia Rahman', 'subject' => 'Campus visit', 'status' => 'new']);
        $this->post('/contact/enquiries', ['name' => 'Invalid sender', 'email' => 'not-an-email', 'subject' => 'Question', 'message' => 'Hello'])->assertSessionHasErrors('email');
    }

    public function test_admin_can_review_search_and_delete_an_enquiry(): void
    {
        $admin = $this->admin();
        $enquiry = ContactEnquiry::create(['name' => 'Nabila Islam', 'email' => 'nabila@example.test', 'subject' => 'Transport route', 'message' => 'Do you offer transport from our area?']);

        $this->actingAs($admin)->get('/admin/contact-enquiries?search=Transport')->assertOk();
        $this->actingAs($admin)->get("/admin/contact-enquiries/{$enquiry->id}")->assertOk();
        $this->actingAs($admin)->put("/admin/contact-enquiries/{$enquiry->id}", ['status' => 'contacted', 'admin_notes' => 'Called and shared the available route.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contact_enquiries', ['id' => $enquiry->id, 'status' => 'contacted', 'handled_by' => $admin->id]);
        $this->actingAs($admin)->delete("/admin/contact-enquiries/{$enquiry->id}")->assertRedirect('/admin/contact-enquiries');
        $this->assertDatabaseMissing('contact_enquiries', ['id' => $enquiry->id]);
    }

    public function test_non_admin_cannot_access_contact_inbox(): void
    {
        $role = Role::create(['name' => 'Teacher', 'slug' => 'teacher']);
        $teacher = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($teacher)->get('/admin/contact-enquiries')->assertForbidden();
    }
}
