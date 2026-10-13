<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentSectionRequest;
use App\Models\ContentSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ContentSectionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ContentSection::class);
        $page = $request->query('page', 'home');
        abort_unless(in_array($page, $this->pages(), true), 404);

        return Inertia::render('Admin/ContentSections/Index', [
            'page' => $page,
            'pages' => $this->pages(),
            'sections' => ContentSection::query()->where('page_slug', $page)->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ContentSection::class);

        return Inertia::render('Admin/ContentSections/Form', ['pages' => $this->pages(), 'defaultPage' => $request->query('page', 'home')]);
    }

    public function store(ContentSectionRequest $request): RedirectResponse
    {
        $this->authorize('create', ContentSection::class);

        $content = DB::transaction(function () use ($request) {
            $content = ContentSection::create($request->validated());
            $this->log($request, 'content.created', $content);

            return $content;
        });

        return to_route('admin.content.index', ['page' => $content->page_slug])->with('success', 'Website section created.');
    }

    public function edit(ContentSection $content): Response
    {
        $this->authorize('update', $content);

        return Inertia::render('Admin/ContentSections/Form', ['content' => $content, 'pages' => $this->pages()]);
    }

    public function update(ContentSectionRequest $request, ContentSection $content): RedirectResponse
    {
        $this->authorize('update', $content);

        DB::transaction(function () use ($request, $content) {
            $content->update($request->validated());
            $this->log($request, 'content.updated', $content);
        });

        return to_route('admin.content.index', ['page' => $content->page_slug])->with('success', 'Website section saved.');
    }

    public function destroy(Request $request, ContentSection $content): RedirectResponse
    {
        $this->authorize('delete', $content);

        DB::transaction(function () use ($request, $content) {
            $this->log($request, 'content.deleted', $content);
            $content->delete();
        });

        return to_route('admin.content.index', ['page' => $content->page_slug])->with('success', 'Website section deleted.');
    }

    private function pages(): array
    {
        return ['home', 'about', 'academics', 'admissions', 'contact'];
    }

    private function log(Request $request, string $action, ContentSection $content): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => ContentSection::class,
            'subject_id' => $content->id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
