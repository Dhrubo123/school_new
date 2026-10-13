<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdmissionApplicationRequest;
use App\Models\AdmissionApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdmissionController extends Controller
{
    public function store(AdmissionApplicationRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            AdmissionApplication::create([...$request->validated(), 'application_no' => 'SR-'.now()->format('ymd').'-'.strtoupper(str()->random(5))]);
        });

        return back()->with('success', 'Thank you. Our admissions team will contact you soon.');
    }
}
