<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $slug): User
    {
        $role = Role::create(['name' => str($slug)->replace('-', ' ')->title(), 'slug' => $slug]);

        return User::factory()->create(['role_id' => $role->id, 'password' => 'password']);
    }

    public function test_guests_are_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_non_admin_users_cannot_access_admin(): void
    {
        $this->actingAs($this->user('teacher'))->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_admin_but_not_teacher_portal(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/teacher')->assertForbidden();
    }

    public function test_login_redirects_each_role_to_its_portal(): void
    {
        $teacher = $this->user('teacher');
        $this->post('/login', ['email' => $teacher->email, 'password' => 'password'])->assertRedirect('/teacher');
    }
}
