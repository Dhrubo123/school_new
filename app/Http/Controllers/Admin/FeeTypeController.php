<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FeeTypeRequest;
use App\Models\FeeType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FeeTypeController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', FeeType::class);

        return Inertia::render('Admin/FeeTypes/Index', ['feeTypes' => FeeType::query()->orderBy('sort_order')->orderBy('name')->get(), 'frequencies' => FeeType::FREQUENCIES]);
    }

    public function create(): Response
    {
        $this->authorize('create', FeeType::class);

        return Inertia::render('Admin/FeeTypes/Form', ['frequencies' => FeeType::FREQUENCIES]);
    }

    public function store(FeeTypeRequest $request): RedirectResponse
    {
        $feeType = DB::transaction(function () use ($request) {
            $feeType = FeeType::create($this->data($request));
            $this->log($request, 'fee-type.created', $feeType);

            return $feeType;
        });

        return to_route('admin.fee-types.index')->with('success', "{$feeType->name} fee type created.");
    }

    public function edit(FeeType $feeType): Response
    {
        $this->authorize('update', $feeType);

        return Inertia::render('Admin/FeeTypes/Form', ['feeType' => $feeType, 'frequencies' => FeeType::FREQUENCIES]);
    }

    public function update(FeeTypeRequest $request, FeeType $feeType): RedirectResponse
    {
        DB::transaction(function () use ($request, $feeType) {
            $feeType->update($this->data($request));
            $this->log($request, 'fee-type.updated', $feeType);
        });

        return to_route('admin.fee-types.index')->with('success', 'Fee type saved.');
    }

    public function destroy(Request $request, FeeType $feeType): RedirectResponse
    {
        $this->authorize('delete', $feeType);
        DB::transaction(function () use ($request, $feeType) {
            $this->log($request, 'fee-type.deleted', $feeType);
            $feeType->delete();
        });

        return back()->with('success', 'Fee type deleted. Existing invoice lines remain unchanged.');
    }

    private function data(FeeTypeRequest $request): array
    {
        return [...$request->validated(), 'is_active' => $request->boolean('is_active'), 'sort_order' => $request->integer('sort_order', 0)];
    }

    private function log(Request $request, string $action, FeeType $feeType): void
    {
        DB::table('activity_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'subject_type' => FeeType::class, 'subject_id' => $feeType->id, 'ip_address' => $request->ip(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
