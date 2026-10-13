<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_admin_can_create_publish_and_delete_an_album(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/galleries', [
            'title' => 'Annual Science Fair 2026',
            'description' => 'Student projects and demonstrations.',
            'is_published' => true,
            'published_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $gallery = Gallery::firstOrFail();
        $this->assertSame('annual-science-fair-2026', $gallery->slug);
        $this->actingAs($admin)->get('/admin/galleries?search=Science')->assertOk();
        $this->actingAs($admin)->delete("/admin/galleries/{$gallery->id}")->assertRedirect('/admin/galleries');
        $this->assertDatabaseMissing('galleries', ['id' => $gallery->id]);
    }

    public function test_admin_can_upload_caption_reorder_and_delete_gallery_images(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $gallery = Gallery::create(['title' => 'Campus life', 'slug' => 'campus-life']);
        $files = [UploadedFile::fake()->image('science-lab.jpg'), UploadedFile::fake()->image('debate.webp')];

        $this->actingAs($admin)->post("/admin/galleries/{$gallery->id}/images", ['images' => $files])->assertSessionHasNoErrors();
        $images = $gallery->fresh()->images;
        $this->assertCount(2, $images);
        Storage::disk('public')->assertExists($images->first()->path);

        $image = $images->first();
        $this->actingAs($admin)->patch("/admin/galleries/{$gallery->id}/images/{$image->id}", ['alt_text' => 'Students demonstrating a solar project'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('gallery_images', ['id' => $image->id, 'alt_text' => 'Students demonstrating a solar project']);

        $reversed = $images->pluck('id')->reverse()->values()->all();
        $this->actingAs($admin)->patch("/admin/galleries/{$gallery->id}/images/reorder", ['image_ids' => $reversed])->assertSessionHasNoErrors();
        $this->assertSame($reversed, $gallery->fresh()->images->pluck('id')->all());

        $this->actingAs($admin)->delete("/admin/galleries/{$gallery->id}/images/{$image->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('gallery_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_non_admin_cannot_manage_galleries(): void
    {
        $teacherRole = Role::create(['name' => 'Teacher', 'slug' => 'teacher']);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id]);
        $this->actingAs($teacher)->get('/admin/galleries')->assertForbidden();
    }

    public function test_public_gallery_only_shows_published_albums(): void
    {
        $published = Gallery::create(['title' => 'Published album', 'slug' => 'published-album', 'is_published' => true, 'published_at' => now()->subMinute()]);
        $draft = Gallery::create(['title' => 'Draft album', 'slug' => 'draft-album', 'is_published' => false]);
        $published->images()->create(['path' => 'galleries/1/photo.jpg', 'alt_text' => 'School activity', 'sort_order' => 1]);
        $draft->images()->create(['path' => 'galleries/2/private.jpg', 'alt_text' => 'Private activity', 'sort_order' => 1]);

        $response = $this->get('/gallery');
        $response->assertOk()->assertInertia(fn ($page) => $page->has('galleries', 1)->where('galleries.0.title', 'Published album'));
    }
}
