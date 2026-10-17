<?php

namespace App\Http\Requests\Admin;

use App\Models\FeeInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeInvoiceBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FeeInvoice::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')],
            'school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
            'school_section_id' => ['required', 'integer', Rule::exists('school_sections', 'id')],
            'title' => ['required', 'string', 'max:180'],
            'billing_period' => ['required', 'regex:/^\\d{4}-(0[1-9]|1[0-2])$/'],
            'issued_on' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issued_on'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'fee_type_ids' => ['required', 'array', 'min:1', 'max:30'],
            'fee_type_ids.*' => ['integer', 'distinct', Rule::exists('fee_types', 'id')],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
