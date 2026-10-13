<?php

namespace Tests\Feature;

use App\Models\HomeBanner;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_and_publish_a_homepage_banner(): void
    {
        Storage::fake('public');
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $this->actingAs($admin)->post('/admin/banners', [
            'title' => 'Welcome to our school',
            'subtitle' => 'A place where every learner can thrive.',
            'primary_button_label' => 'Apply now',
            'primary_button_url' => '/admissions',
            'secondary_button_label' => 'About us',
            'secondary_button_url' => '/about',
            'sort_order' => 1,
            'is_published' => true,
            'image' => UploadedFile::fake()->image('homepage-banner.jpg', 1600, 700),
        ])->assertRedirect('/admin/banners')->assertSessionHasNoErrors();

        $banner = HomeBanner::firstOrFail();
        $this->assertTrue($banner->is_published);
        Storage::disk('public')->assertExists($banner->image_path);
        HomeBanner::create(['title' => 'Private draft', 'is_published' => false]);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->has('banners', 1)
            ->where('banners.0.title', 'Welcome to our school')
        );
    }

    public function test_non_admin_cannot_manage_homepage_banners(): void
    {
        $teacherRole = Role::create(['name' => 'Teacher', 'slug' => 'teacher']);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id]);

        $this->actingAs($teacher)->get('/admin/banners')->assertForbidden();
    }
}
