<?php

namespace App\Http\Requests\Admin;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('fee_invoice') ?? FeeInvoice::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'payment_date' => ['required', 'date'],
            'method' => ['required', Rule::in(FeePayment::METHODS)],
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
