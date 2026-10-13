<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $content = $this->route('content');

        return [
            'page_slug' => ['required', Rule::in(['home', 'about', 'academics', 'admissions', 'contact'])],
            'section_key' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', Rule::unique('content_sections', 'section_key')->where('page_slug', $this->input('page_slug'))->ignore($content)],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
            'is_published' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
