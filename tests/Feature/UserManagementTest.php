<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $slug, string $name): Role
    {
        return Role::create(['slug' => $slug, 'name' => $name]);
    }

    public function test_super_admin_can_create_update_and_delete_managed_users(): void
    {
        $superAdminRole = $this->role('super-admin', 'Super Admin');
        $teacherRole = $this->role('teacher', 'Teacher');
        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

        $this->actingAs($superAdmin)->post('/admin/users', [
            'name' => 'Nusrat Jahan',
            'email' => 'nusrat@example.com',
            'role_id' => $teacherRole->id,
            'password' => 'School@123',
            'password_confirmation' => 'School@123',
        ])->assertRedirect('/admin/users');

        $user = User::where('email', 'nusrat@example.com')->firstOrFail();
        $this->actingAs($superAdmin)->put("/admin/users/{$user->id}", [
            'name' => 'Nusrat Sultana',
            'email' => 'nusrat@example.com',
            'role_id' => $teacherRole->id,
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nusrat Sultana']);

        $this->actingAs($superAdmin)->delete("/admin/users/{$user->id}")->assertRedirect('/admin/users');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_only_manage_lower_privilege_roles(): void
    {
        $superAdminRole = $this->role('super-admin', 'Super Admin');
        $adminRole = $this->role('admin', 'Admin');
        $teacherRole = $this->role('teacher', 'Teacher');
        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertDontSee($superAdmin->email);
        $this->actingAs($admin)->get("/admin/users/{$superAdmin->id}/edit")->assertForbidden();
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Blocked Admin',
            'email' => 'blocked@example.com',
            'role_id' => $adminRole->id,
            'password' => 'School@123',
            'password_confirmation' => 'School@123',
        ])->assertForbidden();
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Teacher',
            'email' => 'teacher@example.com',
            'role_id' => $teacherRole->id,
            'password' => 'School@123',
            'password_confirmation' => 'School@123',
        ])->assertRedirect('/admin/users');
    }

    public function test_last_super_admin_cannot_be_deleted(): void
    {
        $superAdminRole = $this->role('super-admin', 'Super Admin');
        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $otherSuperAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

        $this->actingAs($otherSuperAdmin)->delete("/admin/users/{$superAdmin->id}")->assertRedirect('/admin/users');
        $this->actingAs($otherSuperAdmin)->delete("/admin/users/{$otherSuperAdmin->id}")->assertForbidden();
    }
}
