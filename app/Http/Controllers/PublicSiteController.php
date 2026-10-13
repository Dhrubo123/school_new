<?php

namespace App\Http\Controllers;

use App\Models\ContentSection;
use App\Models\Gallery;
use App\Models\Notice;
use App\Models\SchoolEvent;
use Inertia\Inertia;
use Inertia\Response;

class PublicSiteController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Public/Home', [
            'notices' => Notice::published()->latest('published_at')->limit(4)->get(),
            'events' => SchoolEvent::published()->orderBy('starts_at')->limit(4)->get(),
            'contentSections' => $this->publishedSections('home'),
        ]);
    }

    public function page(string $page): Response
    {
        abort_unless(in_array($page, ['about', 'academics', 'admissions', 'gallery', 'contact'], true), 404);

        return Inertia::render('Public/Page', [
            'page' => $page,
            'galleries' => $page === 'gallery' ? Gallery::published()->with('images')->latest('published_at')->get() : [],
            'contentSections' => $page === 'gallery' ? [] : $this->publishedSections($page),
        ]);
    }

    public function news(): Response
    {
        return Inertia::render('Public/News', [
            'notices' => Notice::published()->latest('published_at')->paginate(9, ['*'], 'notices'),
            'events' => SchoolEvent::published()->orderBy('starts_at')->paginate(9, ['*'], 'events'),
        ]);
    }

    private function publishedSections(string $page): mixed
    {
        return ContentSection::published()->where('page_slug', $page)->orderBy('sort_order')->orderBy('id')->get(['id', 'section_key', 'title', 'body', 'sort_order']);
    }
}
