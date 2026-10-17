<?php

namespace App\Http\Requests\Admin;

use App\Models\FeeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $feeType = $this->route('fee_type');

        return $this->user()?->can($feeType ? 'update' : 'create', $feeType ?? FeeType::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'frequency' => ['required', Rule::in(FeeType::FREQUENCIES)],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
