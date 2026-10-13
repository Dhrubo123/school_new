<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdmissionReviewRequest;
use App\Models\AdmissionApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdmissionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AdmissionApplication::class);
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');

        return Inertia::render('Admin/Admissions/Index', [
            'search' => $search,
            'status' => $status,
            'applications' => AdmissionApplication::query()
                ->with('reviewer:id,name')
                ->when($search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('application_no', 'like', "%{$search}%")
                    ->orWhere('student_name', 'like', "%{$search}%")
                    ->orWhere('guardian_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")))
                ->when(in_array($status, AdmissionApplication::STATUSES, true), fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'statusOptions' => AdmissionApplication::STATUSES,
        ]);
    }

    public function show(AdmissionApplication $admission): Response
    {
        $this->authorize('view', $admission);

        return Inertia::render('Admin/Admissions/Show', [
            'admission' => $admission->load('reviewer:id,name'),
            'statusOptions' => AdmissionApplication::STATUSES,
        ]);
    }

    public function update(AdmissionReviewRequest $request, AdmissionApplication $admission): RedirectResponse
    {
        DB::transaction(function () use ($request, $admission) {
            $admission->update([
                ...$request->validated(),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            DB::table('activity_logs')->insert([
                'user_id' => $request->user()->id,
                'action' => 'admission.reviewed',
                'subject_type' => AdmissionApplication::class,
                'subject_id' => $admission->id,
                'properties' => json_encode(['status' => $admission->status]),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Application review saved.');
    }
}
