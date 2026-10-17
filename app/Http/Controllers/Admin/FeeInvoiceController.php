<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FeeInvoiceBatchRequest;
use App\Http\Requests\Admin\FeeInvoiceRequest;
use App\Http\Requests\Admin\FeePaymentRequest;
use App\Models\AcademicYear;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\SchoolSection;
use App\Models\StudentProfile;
use App\Services\FeeInvoiceCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FeeInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FeeInvoice::class);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', 'in:unpaid,partial,paid,void']]);
        $query = FeeInvoice::query()
            ->with(['studentProfile.user:id,name', 'studentProfile.schoolClass:id,name', 'studentProfile.schoolSection:id,name', 'academicYear:id,name'])
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->when($filters['search'] ?? null, fn ($builder, $search) => $builder->where(fn ($inner) => $inner->where('invoice_no', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%")->orWhereHas('studentProfile.user', fn ($users) => $users->where('name', 'like', "%{$search}%"))));
        $totalsQuery = clone $query;

        return Inertia::render('Admin/FeeInvoices/Index', [
            'filters' => $filters,
            'statuses' => FeeInvoice::STATUSES,
            'totals' => [
                'invoiced' => (float) (clone $totalsQuery)->where('status', '!=', 'void')->sum('total_amount'),
                'collected' => (float) (clone $totalsQuery)->where('status', '!=', 'void')->sum('paid_amount'),
                'outstanding' => (float) (clone $totalsQuery)->where('status', '!=', 'void')->sum('balance_amount'),
                'overdue' => (float) (clone $totalsQuery)->where('status', '!=', 'void')->where('balance_amount', '>', 0)->whereDate('due_date', '<', today())->sum('balance_amount'),
            ],
            'invoices' => $query->orderByRaw("CASE status WHEN 'unpaid' THEN 1 WHEN 'partial' THEN 2 WHEN 'paid' THEN 3 ELSE 4 END")->orderBy('due_date')->latest('id')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', FeeInvoice::class);

        return Inertia::render('Admin/FeeInvoices/Form', $this->formData());
    }

    public function store(FeeInvoiceRequest $request, FeeInvoiceCalculator $calculator): RedirectResponse
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);
        $invoice = DB::transaction(function () use ($request, $data, $items, $calculator) {
            $invoice = FeeInvoice::create([...$data, 'invoice_no' => $this->number('INV'), 'discount_amount' => $data['discount_amount'] ?? 0, 'created_by' => $request->user()->id]);
            $invoice->items()->createMany($items);
            $this->log($request, 'fee-invoice.created', $invoice);

            return $calculator->refresh($invoice);
        });

        return to_route('admin.fee-invoices.show', $invoice)->with('success', 'Fee invoice created. You can record payment when money is received.');
    }

    public function show(FeeInvoice $feeInvoice): Response
    {
        $this->authorize('view', $feeInvoice);

        return Inertia::render('Admin/FeeInvoices/Show', ['invoice' => $feeInvoice->load(['studentProfile.user:id,name,email', 'studentProfile.academicYear:id,name', 'studentProfile.schoolClass:id,name', 'studentProfile.schoolSection:id,name', 'academicYear:id,name', 'items.feeType:id,name', 'payments.receivedBy:id,name']), 'methods' => FeePayment::METHODS]);
    }

    public function edit(FeeInvoice $feeInvoice): Response
    {
        $this->authorize('update', $feeInvoice);
        $this->assertEditable($feeInvoice);

        return Inertia::render('Admin/FeeInvoices/Form', [...$this->formData(), 'invoice' => $feeInvoice->load('items')]);
    }

    public function update(FeeInvoiceRequest $request, FeeInvoice $feeInvoice, FeeInvoiceCalculator $calculator): RedirectResponse
    {
        $this->authorize('update', $feeInvoice);
        $this->assertEditable($feeInvoice);
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);
        DB::transaction(function () use ($request, $feeInvoice, $data, $items, $calculator) {
            $feeInvoice->update([...$data, 'discount_amount' => $data['discount_amount'] ?? 0]);
            $feeInvoice->items()->delete();
            $feeInvoice->items()->createMany($items);
            $calculator->refresh($feeInvoice);
            $this->log($request, 'fee-invoice.updated', $feeInvoice);
        });

        return to_route('admin.fee-invoices.show', $feeInvoice)->with('success', 'Fee invoice updated.');
    }

    public function destroy(Request $request, FeeInvoice $feeInvoice): RedirectResponse
    {
        $this->authorize('delete', $feeInvoice);
        $this->assertEditable($feeInvoice);
        DB::transaction(function () use ($request, $feeInvoice) {
            $this->log($request, 'fee-invoice.deleted', $feeInvoice);
            $feeInvoice->delete();
        });

        return to_route('admin.fee-invoices.index')->with('success', 'Fee invoice deleted.');
    }

    public function createBatch(): Response
    {
        $this->authorize('create', FeeInvoice::class);

        return Inertia::render('Admin/FeeInvoices/Generate', $this->batchData());
    }

    public function storeBatch(FeeInvoiceBatchRequest $request, FeeInvoiceCalculator $calculator): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureSectionMatchesClass($data);
        $feeTypes = FeeType::active()->whereIn('id', $data['fee_type_ids'])->orderBy('sort_order')->get();
        if ($feeTypes->count() !== count($data['fee_type_ids'])) {
            throw ValidationException::withMessages(['fee_type_ids' => 'Choose active fee types only.']);
        }
        $students = StudentProfile::query()->where('academic_year_id', $data['academic_year_id'])->where('school_class_id', $data['school_class_id'])->where('school_section_id', $data['school_section_id'])->get();
        if ($students->isEmpty()) {
            throw ValidationException::withMessages(['school_section_id' => 'There are no students in this academic year, class, and section.']);
        }

        $created = DB::transaction(function () use ($request, $data, $feeTypes, $students, $calculator) {
            $created = 0;
            foreach ($students as $student) {
                $exists = FeeInvoice::query()->where('student_profile_id', $student->id)->where('academic_year_id', $data['academic_year_id'])->where('billing_period', $data['billing_period'])->where('title', $data['title'])->where('status', '!=', 'void')->exists();
                if ($exists) {
                    continue;
                }
                $invoice = FeeInvoice::create([
                    'student_profile_id' => $student->id,
                    'academic_year_id' => $data['academic_year_id'],
                    'invoice_no' => $this->number('INV'),
                    'title' => $data['title'],
                    'billing_period' => $data['billing_period'],
                    'issued_on' => $data['issued_on'],
                    'due_date' => $data['due_date'] ?? null,
                    'discount_amount' => $data['discount_amount'] ?? 0,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);
                $invoice->items()->createMany($feeTypes->map(fn (FeeType $feeType) => ['fee_type_id' => $feeType->id, 'title' => $feeType->name, 'amount' => $feeType->default_amount])->all());
                $calculator->refresh($invoice);
                $this->log($request, 'fee-invoice.batch-created', $invoice);
                $created++;
            }

            return $created;
        });

        return to_route('admin.fee-invoices.index')->with('success', "{$created} invoice(s) generated. Existing invoices for the same student, period, and title were skipped.");
    }

    public function storePayment(FeePaymentRequest $request, FeeInvoice $feeInvoice, FeeInvoiceCalculator $calculator): RedirectResponse
    {
        $this->authorize('update', $feeInvoice);
        $feeInvoice->refresh();
        if ($feeInvoice->status === 'void') {
            throw ValidationException::withMessages(['payment' => 'A void invoice cannot receive payment.']);
        }
        $data = $request->validated();
        if ((float) $data['amount'] > (float) $feeInvoice->balance_amount + 0.004) {
            throw ValidationException::withMessages(['amount' => 'Payment cannot be greater than the current outstanding balance.']);
        }
        DB::transaction(function () use ($request, $feeInvoice, $data, $calculator) {
            $payment = $feeInvoice->payments()->create([...$data, 'receipt_no' => $this->number('RCP'), 'received_by' => $request->user()->id]);
            $calculator->refresh($feeInvoice);
            $this->log($request, 'fee-payment.created', $feeInvoice, ['payment_id' => $payment->id, 'amount' => $payment->amount]);
        });

        return back()->with('success', 'Payment recorded and receipt created.');
    }

    public function destroyPayment(Request $request, FeeInvoice $feeInvoice, FeePayment $payment, FeeInvoiceCalculator $calculator): RedirectResponse
    {
        $this->authorize('update', $feeInvoice);
        abort_unless($payment->fee_invoice_id === $feeInvoice->id, 404);
        DB::transaction(function () use ($request, $feeInvoice, $payment, $calculator) {
            $payment->delete();
            $calculator->refresh($feeInvoice);
            $this->log($request, 'fee-payment.deleted', $feeInvoice, ['payment_id' => $payment->id]);
        });

        return back()->with('success', 'Payment removed and invoice balance recalculated.');
    }

    private function formData(): array
    {
        return [
            'students' => StudentProfile::query()->with(['user:id,name', 'academicYear:id,name', 'schoolClass:id,name', 'schoolSection:id,name'])->orderBy('student_no')->get(['id', 'user_id', 'student_no', 'academic_year_id', 'school_class_id', 'school_section_id']),
            'years' => AcademicYear::query()->orderByDesc('is_current')->orderByDesc('starts_on')->get(['id', 'name', 'is_current']),
            'feeTypes' => FeeType::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'frequency', 'default_amount']),
        ];
    }

    private function batchData(): array
    {
        return [...$this->formData(), 'classes' => SchoolClass::query()->with('sections:id,school_class_id,name')->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])];
    }

    private function assertEditable(FeeInvoice $feeInvoice): void
    {
        if ($feeInvoice->payments()->exists()) {
            throw ValidationException::withMessages(['invoice' => 'Invoices with payment records cannot be edited or deleted. Remove incorrect payments first.']);
        }
    }

    private function ensureSectionMatchesClass(array $data): void
    {
        if (! SchoolSection::query()->whereKey($data['school_section_id'])->where('school_class_id', $data['school_class_id'])->exists()) {
            throw ValidationException::withMessages(['school_section_id' => 'Choose a section that belongs to the selected class.']);
        }
    }

    private function number(string $prefix): string
    {
        return $prefix.'-'.now()->format('ymd').'-'.str()->upper(str()->random(6));
    }

    private function log(Request $request, string $action, FeeInvoice $invoice, array $properties = []): void
    {
        DB::table('activity_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'subject_type' => FeeInvoice::class, 'subject_id' => $invoice->id, 'properties' => json_encode($properties), 'ip_address' => $request->ip(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
