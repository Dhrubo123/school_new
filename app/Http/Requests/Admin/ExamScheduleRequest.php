<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('examination')) ?? false;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
            'school_section_id' => ['required', 'integer', Rule::exists('school_sections', 'id')],
            'exam_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'full_marks' => ['required', 'numeric', 'gt:0', 'max:9999'],
            'pass_marks' => ['required', 'numeric', 'min:0', 'lte:full_marks'],
        ];
    }
}
