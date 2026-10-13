<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GalleryImageRequest;
use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GalleryImageController extends Controller
{
    public function store(GalleryImageRequest $request, Gallery $gallery): RedirectResponse
    {
        $highestOrder = (int) $gallery->images()->max('sort_order');

        DB::transaction(function () use ($request, $gallery, &$highestOrder) {
            foreach ($request->file('images') as $image) {
                $path = $image->store("galleries/{$gallery->id}", 'public');
                $gallery->images()->create(['path' => $path, 'alt_text' => pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME), 'sort_order' => ++$highestOrder]);
            }
        });

        return back()->with('success', 'Photographs uploaded successfully.');
    }

    public function update(Request $request, Gallery $gallery, GalleryImage $image): RedirectResponse
    {
        $this->authorize('update', $gallery);
        abort_unless($image->gallery_id === $gallery->id, 404);
        $validated = $request->validate(['alt_text' => ['required', 'string', 'max:180']]);
        $image->update($validated);

        return back()->with('success', 'Caption updated.');
    }

    public function reorder(Request $request, Gallery $gallery): RedirectResponse
    {
        $this->authorize('update', $gallery);
        $validated = $request->validate(['image_ids' => ['required', 'array'], 'image_ids.*' => ['integer', 'distinct']]);
        $ids = $gallery->images()->pluck('id')->all();
        abort_unless(count($ids) === count($validated['image_ids']) && empty(array_diff($ids, $validated['image_ids'])), 422);

        DB::transaction(function () use ($gallery, $validated) {
            foreach ($validated['image_ids'] as $position => $id) {
                $gallery->images()->whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return back()->with('success', 'Image order updated.');
    }

    public function destroy(Request $request, Gallery $gallery, GalleryImage $image): RedirectResponse
    {
        $this->authorize('update', $gallery);
        abort_unless($image->gallery_id === $gallery->id, 404);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Photograph deleted.');
    }
}
