<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\SchoolEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublishedContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_currently_published_content_appears_publicly(): void
    {
        $author = User::factory()->create();
        Notice::factory()->for($author, 'author')->create(['title' => 'Public notice', 'status' => 'published', 'published_at' => now()->subMinute()]);
        Notice::factory()->for($author, 'author')->draft()->create(['title' => 'Private draft']);
        Notice::factory()->for($author, 'author')->create(['title' => 'Future notice', 'published_at' => now()->addDay()]);
        SchoolEvent::factory()->for($author, 'author')->create(['title' => 'Public event']);
        SchoolEvent::factory()->for($author, 'author')->draft()->create(['title' => 'Draft event']);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')->has('notices', 1)->where('notices.0.title', 'Public notice')->has('events', 1)->where('events.0.title', 'Public event'));
    }
}
