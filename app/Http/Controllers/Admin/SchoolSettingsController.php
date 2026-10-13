<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchoolSettingsRequest;
use App\Models\SchoolSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $this->authorize('viewAny', SchoolSetting::class);

        return Inertia::render('Admin/Settings/Edit', ['settings' => SchoolSetting::values()]);
    }

    public function update(SchoolSettingsRequest $request): RedirectResponse
    {
        $previousLogo = SchoolSetting::query()->where('key', 'logo_path')->value('value');
        $values = $request->safe()->except('logo');

        DB::transaction(function () use ($request, &$values) {
            if ($request->hasFile('logo')) {
                $values['logo_path'] = $request->file('logo')->store('branding', 'public');
            }

            foreach ($values as $key => $value) {
                SchoolSetting::query()->updateOrCreate(['key' => $key], ['value' => $value ?: null, 'type' => $key === 'logo_path' ? 'image' : 'text']);
            }

            DB::table('activity_logs')->insert([
                'user_id' => $request->user()->id,
                'action' => 'settings.updated',
                'properties' => json_encode(['keys' => array_keys($values)]),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        if (isset($values['logo_path']) && $previousLogo && $previousLogo !== $values['logo_path']) {
            Storage::disk('public')->delete($previousLogo);
        }

        return back()->with('success', 'School settings saved.');
    }
}
