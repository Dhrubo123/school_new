<?php

namespace App\Http\Requests\Admin;

use App\Models\Examination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExaminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $examination = $this->route('examination');

        return $this->user()?->can($examination ? 'update' : 'create', $examination ?? Examination::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')],
            'title' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(Examination::TYPES)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }
}
