<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeePortalController extends Controller
{
    public function student(Request $request): Response
    {
        $student = $request->user()?->studentProfile?->load('user:id,name', 'academicYear:id,name', 'schoolClass:id,name', 'schoolSection:id,name');
        abort_unless($student, 403);

        return $this->forStudent('student', $student, collect());
    }

    public function guardian(Request $request): Response
    {
        $students = $request->user()?->linkedStudents()
            ->with(['user:id,name', 'academicYear:id,name', 'schoolClass:id,name', 'schoolSection:id,name'])
            ->get() ?? collect();
        $student = $students->firstWhere('id', (int) $request->query('student')) ?? $students->first();
        abort_unless($student, 403);

        return $this->forStudent('guardian', $student, $students);
    }

    private function forStudent(string $role, StudentProfile $student, $students): Response
    {
        return Inertia::render('Portal/Fees', [
            'role' => $role,
            'student' => $student,
            'students' => collect($students)->map(fn (StudentProfile $item) => [
                'id' => $item->id,
                'name' => $item->user?->name,
                'class_name' => $item->schoolClass?->name,
                'section_name' => $item->schoolSection?->name,
            ])->values(),
            'invoices' => $student->feeInvoices()
                ->with(['items.feeType:id,name', 'payments.receivedBy:id,name'])
                ->where('status', '!=', 'void')
                ->orderByRaw("CASE status WHEN 'unpaid' THEN 1 WHEN 'partial' THEN 2 ELSE 3 END")
                ->orderBy('due_date')
                ->latest('id')
                ->get(),
        ]);
    }
}
