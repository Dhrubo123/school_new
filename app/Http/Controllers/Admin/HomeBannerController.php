<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomeBannerRequest;
use App\Models\HomeBanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeBannerController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', HomeBanner::class);

        return Inertia::render('Admin/Banners/Index', [
            'banners' => HomeBanner::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', HomeBanner::class);

        return Inertia::render('Admin/Banners/Form');
    }

    public function store(HomeBannerRequest $request): RedirectResponse
    {
        $banner = DB::transaction(function () use ($request) {
            $banner = HomeBanner::create($this->bannerData($request));
            $this->log($request, 'home-banner.created', $banner);

            return $banner;
        });

        return to_route('admin.banners.index')->with('success', "{$banner->title} banner created.");
    }

    public function edit(HomeBanner $banner): Response
    {
        $this->authorize('update', $banner);

        return Inertia::render('Admin/Banners/Form', ['banner' => $banner]);
    }

    public function update(HomeBannerRequest $request, HomeBanner $banner): RedirectResponse
    {
        $oldImage = $banner->image_path;
        DB::transaction(function () use ($request, $banner) {
            $banner->update($this->bannerData($request, $banner));
            $this->log($request, 'home-banner.updated', $banner);
        });

        if ($oldImage && $oldImage !== $banner->image_path) {
            Storage::disk('public')->delete($oldImage);
        }

        return to_route('admin.banners.index')->with('success', 'Homepage banner saved.');
    }

    public function destroy(Request $request, HomeBanner $banner): RedirectResponse
    {
        $this->authorize('delete', $banner);
        $imagePath = $banner->image_path;
        DB::transaction(function () use ($request, $banner) {
            $this->log($request, 'home-banner.deleted', $banner);
            $banner->delete();
        });
        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return back()->with('success', 'Homepage banner deleted.');
    }

    private function bannerData(HomeBannerRequest $request, ?HomeBanner $banner = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] ??= 0;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('banners', 'public');
        } elseif ($banner?->image_path && $request->boolean('remove_image')) {
            $data['image_path'] = null;
        }

        return $data;
    }

    private function log(Request $request, string $action, HomeBanner $banner): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => HomeBanner::class,
            'subject_id' => $banner->id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
