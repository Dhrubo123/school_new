<?php

namespace App\Http\Requests\Admin;

use App\Models\AdmissionApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdmissionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admission = $this->route('admission');

        return $this->user()?->can('update', $admission ?? AdmissionApplication::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['new', 'contacted', 'reviewing', 'accepted', 'declined'])],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
