<?php

namespace App\Http\Requests\Admin;

use App\Models\ContactEnquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactEnquiryReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('contact_enquiry')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ContactEnquiry::STATUSES)],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
