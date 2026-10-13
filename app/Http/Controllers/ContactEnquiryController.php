<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactEnquiryRequest;
use App\Models\ContactEnquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ContactEnquiryController extends Controller
{
    public function store(ContactEnquiryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            ContactEnquiry::create($request->validated());
        });

        return back()->with('success', 'Thank you for your message. Our school office will respond soon.');
    }
}
