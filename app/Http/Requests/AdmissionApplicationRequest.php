<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdmissionApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_name' => ['required', 'string', 'max:120'],
            'guardian_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['required', 'regex:/^(?:\\+?88)?01[3-9]\\d{8}$/'],
            'applying_for_class' => ['required', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Enter a valid Bangladeshi mobile number.'];
    }
}
