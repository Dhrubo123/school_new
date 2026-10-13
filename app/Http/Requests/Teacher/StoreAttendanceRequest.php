<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('teacher') ?? false;
    }

    public function rules(): array
    {
        return [
            'teaching_assignment_id' => ['required', 'integer', Rule::exists('teaching_assignments', 'id')],
            'school_section_id' => ['required', 'integer', Rule::exists('school_sections', 'id')],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'period_label' => ['required', 'string', 'max:80'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_profile_id' => ['required', 'integer', 'distinct', Rule::exists('student_profiles', 'id')],
            'records.*.status' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
            'records.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
