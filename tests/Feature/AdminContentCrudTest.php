<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\Role;
use App\Models\SchoolEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContentCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_admin_can_create_update_search_and_delete_a_notice(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/notices', ['title' => 'SSC Routine 2027', 'summary' => 'The final routine is ready.', 'body' => 'Students must arrive thirty minutes before each examination.', 'status' => 'published', 'published_at' => now()->format('Y-m-d H:i:s')])->assertRedirect('/admin/notices');
        $notice = Notice::firstOrFail();
        $this->assertSame($admin->id, $notice->author_id);
        $this->actingAs($admin)->get('/admin/notices?search=SSC')->assertOk();
        $this->actingAs($admin)->put("/admin/notices/{$notice->id}", ['title' => 'Revised SSC Routine 2027', 'summary' => 'The revised final routine is ready.', 'body' => 'Students must arrive thirty minutes before each examination.', 'status' => 'draft', 'published_at' => null])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notices', ['id' => $notice->id, 'status' => 'draft']);
        $this->actingAs($admin)->delete("/admin/notices/{$notice->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('notices', ['id' => $notice->id]);
    }

    public function test_admin_can_create_an_event_and_invalid_dates_are_rejected(): void
    {
        $admin = $this->admin();
        $valid = ['title' => 'Science Fair', 'summary' => 'Student innovation showcase.', 'body' => 'Guardians are invited to visit the exhibition.', 'venue' => 'School Auditorium', 'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'), 'ends_at' => now()->addWeek()->addHours(3)->format('Y-m-d H:i:s'), 'status' => 'published', 'published_at' => now()->format('Y-m-d H:i:s')];
        $this->actingAs($admin)->post('/admin/events', $valid)->assertRedirect('/admin/events');
        $this->assertDatabaseHas('events', ['title' => 'Science Fair']);
        $this->actingAs($admin)->post('/admin/events', [...$valid, 'title' => 'Broken event', 'ends_at' => now()->format('Y-m-d H:i:s')])->assertSessionHasErrors('ends_at');
        $this->assertSame(1, SchoolEvent::count());
    }
}
