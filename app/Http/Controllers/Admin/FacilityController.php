<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FacilityRequest;
use App\Models\SchoolFacility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FacilityController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SchoolFacility::class);
        $search = trim((string) $request->query('search'));

        return Inertia::render('Admin/Facilities/Index', [
            'search' => $search,
            'facilities' => SchoolFacility::query()
                ->when($search, fn ($query) => $query->where(fn ($facility) => $facility->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
                ->orderBy('sort_order')
                ->orderBy('title')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SchoolFacility::class);

        return Inertia::render('Admin/Facilities/Form');
    }

    public function store(FacilityRequest $request): RedirectResponse
    {
        $facility = DB::transaction(function () use ($request) {
            $facility = SchoolFacility::create($this->facilityData($request));
            $this->log($request, 'facility.created', $facility);

            return $facility;
        });

        return to_route('admin.facilities.index')->with('success', "{$facility->title} added to facilities.");
    }

    public function edit(SchoolFacility $facility): Response
    {
        $this->authorize('update', $facility);

        return Inertia::render('Admin/Facilities/Form', ['facility' => $facility]);
    }

    public function update(FacilityRequest $request, SchoolFacility $facility): RedirectResponse
    {
        $oldPhoto = $facility->photo_path;

        DB::transaction(function () use ($request, $facility) {
            $facility->update($this->facilityData($request, $facility));
            $this->log($request, 'facility.updated', $facility);
        });

        if ($oldPhoto && $oldPhoto !== $facility->photo_path) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return to_route('admin.facilities.index')->with('success', 'Facility saved.');
    }

    public function destroy(Request $request, SchoolFacility $facility): RedirectResponse
    {
        $this->authorize('delete', $facility);
        $photoPath = $facility->photo_path;

        DB::transaction(function () use ($request, $facility) {
            $this->log($request, 'facility.deleted', $facility);
            $facility->delete();
        });

        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        return to_route('admin.facilities.index')->with('success', 'Facility deleted.');
    }

    private function facilityData(FacilityRequest $request, ?SchoolFacility $facility = null): array
    {
        $data = $request->safe()->except(['photo', 'remove_photo']);
        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] ??= 0;

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('facilities', 'public');
        } elseif ($facility?->photo_path && $request->boolean('remove_photo')) {
            $data['photo_path'] = null;
        }

        return $data;
    }

    private function log(Request $request, string $action, SchoolFacility $facility): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => SchoolFacility::class,
            'subject_id' => $facility->id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
