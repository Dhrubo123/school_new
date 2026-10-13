<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactEnquiryReviewRequest;
use App\Models\ContactEnquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ContactEnquiryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ContactEnquiry::class);
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');

        return Inertia::render('Admin/Enquiries/Index', [
            'search' => $search,
            'status' => $status,
            'statusOptions' => ContactEnquiry::STATUSES,
            'enquiries' => ContactEnquiry::query()
                ->with('handler:id,name')
                ->when($search, fn ($query) => $query->where(fn ($enquiry) => $enquiry
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")))
                ->when(in_array($status, ContactEnquiry::STATUSES, true), fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function show(ContactEnquiry $contactEnquiry): Response
    {
        $this->authorize('view', $contactEnquiry);

        return Inertia::render('Admin/Enquiries/Show', [
            'enquiry' => $contactEnquiry->load('handler:id,name'),
            'statusOptions' => ContactEnquiry::STATUSES,
        ]);
    }

    public function update(ContactEnquiryReviewRequest $request, ContactEnquiry $contactEnquiry): RedirectResponse
    {
        DB::transaction(function () use ($request, $contactEnquiry) {
            $contactEnquiry->update([
                ...$request->validated(),
                'handled_by' => $request->user()->id,
                'handled_at' => now(),
            ]);
            $this->log($request, 'contact-enquiry.reviewed', $contactEnquiry);
        });

        return back()->with('success', 'Enquiry status saved.');
    }

    public function destroy(Request $request, ContactEnquiry $contactEnquiry): RedirectResponse
    {
        $this->authorize('delete', $contactEnquiry);

        DB::transaction(function () use ($request, $contactEnquiry) {
            $this->log($request, 'contact-enquiry.deleted', $contactEnquiry);
            $contactEnquiry->delete();
        });

        return to_route('admin.contact-enquiries.index')->with('success', 'Enquiry deleted.');
    }

    private function log(Request $request, string $action, ContactEnquiry $enquiry): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => ContactEnquiry::class,
            'subject_id' => $enquiry->id,
            'properties' => json_encode(['status' => $enquiry->status]),
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
