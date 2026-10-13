<?php

namespace App\Http\Requests\Admin;

use App\Models\Timetable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Timetable::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')],
            'school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
            'school_section_id' => ['required', 'integer', Rule::exists('school_sections', 'id')],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'teacher_profile_id' => ['nullable', 'integer', Rule::exists('teacher_profiles', 'id')],
            'day_of_week' => ['required', Rule::in(Timetable::DAYS)],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:100'],
            'is_published' => ['required', 'boolean'],
        ];
    }
}
