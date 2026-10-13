<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GalleryRequest;
use App\Models\Gallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Gallery::class);
        $search = trim((string) $request->query('search'));

        return Inertia::render('Admin/Galleries/Index', [
            'search' => $search,
            'galleries' => Gallery::withCount('images')
                ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Gallery::class);

        return Inertia::render('Admin/Galleries/Form');
    }

    public function store(GalleryRequest $request): RedirectResponse
    {
        $gallery = DB::transaction(function () use ($request) {
            $gallery = Gallery::create([...$request->validated(), 'slug' => $this->slug($request->string('title')->toString())]);
            $this->log($request, 'gallery.created', $gallery);

            return $gallery;
        });

        return to_route('admin.galleries.edit', $gallery)->with('success', 'Album created. Add photographs below.');
    }

    public function edit(Gallery $gallery): Response
    {
        $this->authorize('update', $gallery);

        return Inertia::render('Admin/Galleries/Form', ['gallery' => $gallery->load('images')]);
    }

    public function update(GalleryRequest $request, Gallery $gallery): RedirectResponse
    {
        DB::transaction(function () use ($request, $gallery) {
            $gallery->update([...$request->validated(), 'slug' => $gallery->title === $request->title ? $gallery->slug : $this->slug($request->title, $gallery->id)]);
            $this->log($request, 'gallery.updated', $gallery);
        });

        return back()->with('success', 'Album details saved.');
    }

    public function destroy(Request $request, Gallery $gallery): RedirectResponse
    {
        $this->authorize('delete', $gallery);
        DB::transaction(function () use ($request, $gallery) {
            $this->log($request, 'gallery.deleted', $gallery);
            Storage::disk('public')->delete($gallery->images()->pluck('path')->all());
            $gallery->delete();
        });

        return to_route('admin.galleries.index')->with('success', 'Album deleted. Its images were removed from the gallery.');
    }

    private function slug(string $title, ?int $ignore = null): string
    {
        $base = Str::slug($title) ?: 'gallery';
        $slug = $base;
        $number = 2;
        while (Gallery::where('slug', $slug)->when($ignore, fn ($query) => $query->whereKeyNot($ignore))->exists()) {
            $slug = $base.'-'.$number++;
        }

        return $slug;
    }

    private function log(Request $request, string $action, Gallery $gallery): void
    {
        DB::table('activity_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'subject_type' => Gallery::class, 'subject_id' => $gallery->id, 'ip_address' => $request->ip(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
