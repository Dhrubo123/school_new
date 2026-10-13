<?php

namespace Tests\Feature;

use App\Models\ContentSection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContentSectionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_admin_can_create_edit_hide_and_delete_website_content(): void
    {
        $admin = $this->admin();
        $payload = [
            'page_slug' => 'about',
            'section_key' => 'our-campus',
            'title' => 'Our campus',
            'body' => 'A safe and welcoming place to learn.',
            'is_published' => true,
            'sort_order' => 40,
        ];

        $this->actingAs($admin)->post('/admin/content', $payload)->assertRedirect('/admin/content?page=about');
        $section = ContentSection::where('section_key', 'our-campus')->firstOrFail();
        $this->actingAs($admin)->put("/admin/content/{$section->id}", [...$payload, 'title' => 'Our learning campus', 'is_published' => false])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('content_sections', ['id' => $section->id, 'title' => 'Our learning campus', 'is_published' => false]);
        $this->actingAs($admin)->delete("/admin/content/{$section->id}")->assertRedirect('/admin/content?page=about');
        $this->assertDatabaseMissing('content_sections', ['id' => $section->id]);
    }

    public function test_only_published_sections_are_sent_to_the_public_page(): void
    {
        ContentSection::create(['page_slug' => 'about', 'section_key' => 'published-story', 'title' => 'Published story', 'body' => 'Visible to everyone.', 'is_published' => true, 'sort_order' => 90]);
        ContentSection::create(['page_slug' => 'about', 'section_key' => 'hidden-story', 'title' => 'Hidden story', 'body' => 'Private.', 'is_published' => false, 'sort_order' => 100]);

        $this->get('/about')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Public/Page')->has('contentSections', 5)->where('contentSections.4.title', 'Published story'));
    }

    public function test_non_admin_cannot_manage_website_content(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/content')->assertForbidden();
    }
}
