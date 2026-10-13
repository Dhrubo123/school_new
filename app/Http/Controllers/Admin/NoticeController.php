<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NoticeRequest;
use App\Models\Notice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NoticeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Notice::class);
        $search = trim((string) $request->query('search'));

        return Inertia::render('Admin/Content/Index', [
            'kind' => 'notices', 'search' => $search,
            'items' => Notice::with('author:id,name')->when($search, fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('summary', 'like', "%{$search}%")))->latest()->paginate(10)->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Notice::class);

        return Inertia::render('Admin/Content/Form', ['kind' => 'notices']);
    }

    public function store(NoticeRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $notice = $request->user()->notices()->create([...$request->validated(), 'slug' => $this->slug($request->string('title')->toString())]);
            $this->log($request, 'notice.created', $notice);
        });

        return to_route('admin.notices.index')->with('success', 'Notice created successfully.');
    }

    public function edit(Notice $notice): Response
    {
        $this->authorize('update', $notice);

        return Inertia::render('Admin/Content/Form', ['kind' => 'notices', 'item' => $notice]);
    }

    public function update(NoticeRequest $request, Notice $notice): RedirectResponse
    {
        DB::transaction(function () use ($request, $notice) {
            $notice->update([...$request->validated(), 'slug' => $notice->title === $request->title ? $notice->slug : $this->slug($request->title, $notice->id)]);
            $this->log($request, 'notice.updated', $notice);
        });

        return to_route('admin.notices.index')->with('success', 'Notice updated successfully.');
    }

    public function destroy(Request $request, Notice $notice): RedirectResponse
    {
        $this->authorize('delete', $notice);
        DB::transaction(function () use ($request, $notice) {
            $this->log($request, 'notice.deleted', $notice);
            $notice->delete();
        });

        return back()->with('success', 'Notice deleted.');
    }

    private function slug(string $title, ?int $ignore = null): string
    {
        $base = Str::slug($title) ?: 'notice';
        $slug = $base;
        $i = 2;
        while (Notice::where('slug', $slug)->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function log(Request $request, string $action, Notice $notice): void
    {
        DB::table('activity_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'subject_type' => Notice::class, 'subject_id' => $notice->id, 'ip_address' => $request->ip(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
