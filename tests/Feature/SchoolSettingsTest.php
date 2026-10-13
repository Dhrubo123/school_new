<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_school_settings(): void
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($admin)->get('/admin/settings')->assertOk();
        $this->actingAs($admin)->put('/admin/settings', [
            'school_name' => 'Green Valley School',
            'short_name' => 'Green Valley',
            'eiin' => '123456',
            'phone' => '+880 1711 000000',
            'email' => 'office@greenvalley.edu.bd',
            'address' => 'Uttara, Dhaka',
            'office_hours' => 'Sunday–Thursday · 8:00 am–4:00 pm',
            'facebook_url' => 'https://facebook.com/greenvalleyschool',
            'instagram_url' => '',
            'youtube_url' => '',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('school_settings', ['key' => 'school_name', 'value' => 'Green Valley School']);
        $this->assertSame('office@greenvalley.edu.bd', SchoolSetting::values()['email']);
    }

    public function test_non_admin_cannot_open_school_settings(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/settings')->assertForbidden();
    }
}
