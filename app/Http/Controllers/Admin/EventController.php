<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\SchoolEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SchoolEvent::class);
        $search = trim((string) $request->query('search'));

        return Inertia::render('Admin/Content/Index', [
            'kind' => 'events', 'search' => $search,
            'items' => SchoolEvent::with('author:id,name')->when($search, fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('venue', 'like', "%{$search}%")))->latest()->paginate(10)->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SchoolEvent::class);

        return Inertia::render('Admin/Content/Form', ['kind' => 'events']);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $event = $request->user()->events()->create([...$request->validated(), 'slug' => $this->slug($request->title)]);
            $this->log($request, 'event.created', $event);
        });

        return to_route('admin.events.index')->with('success', 'Event created successfully.');
    }

    public function edit(SchoolEvent $event): Response
    {
        $this->authorize('update', $event);

        return Inertia::render('Admin/Content/Form', ['kind' => 'events', 'item' => $event]);
    }

    public function update(EventRequest $request, SchoolEvent $event): RedirectResponse
    {
        DB::transaction(function () use ($request, $event) {
            $event->update([...$request->validated(), 'slug' => $event->title === $request->title ? $event->slug : $this->slug($request->title, $event->id)]);
            $this->log($request, 'event.updated', $event);
        });

        return to_route('admin.events.index')->with('success', 'Event updated successfully.');
    }

    public function destroy(Request $request, SchoolEvent $event): RedirectResponse
    {
        $this->authorize('delete', $event);
        DB::transaction(function () use ($request, $event) {
            $this->log($request, 'event.deleted', $event);
            $event->delete();
        });

        return back()->with('success', 'Event deleted.');
    }

    private function slug(string $title, ?int $ignore = null): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $i = 2;
        while (SchoolEvent::where('slug', $slug)->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function log(Request $request, string $action, SchoolEvent $event): void
    {
        DB::table('activity_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'subject_type' => SchoolEvent::class, 'subject_id' => $event->id, 'ip_address' => $request->ip(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
