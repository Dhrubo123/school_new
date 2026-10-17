<?php

namespace App\Http\Requests\Admin;

use App\Models\FeeInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('fee_invoice');

        return $this->user()?->can($invoice ? 'update' : 'create', $invoice ?? FeeInvoice::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_profile_id' => ['required', 'integer', Rule::exists('student_profiles', 'id')],
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')],
            'title' => ['required', 'string', 'max:180'],
            'billing_period' => ['nullable', 'regex:/^\\d{4}-(0[1-9]|1[0-2])$/'],
            'issued_on' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issued_on'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.fee_type_id' => ['nullable', 'integer', Rule::exists('fee_types', 'id')],
            'items.*.title' => ['required', 'string', 'max:180'],
            'items.*.amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
        ];
    }
}
