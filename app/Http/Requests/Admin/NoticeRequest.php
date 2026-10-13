<?php

namespace App\Http\Requests\Admin;

use App\Models\Notice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('notice') ? 'update' : 'create', $this->route('notice') ?? Notice::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date', Rule::requiredIf($this->input('status') === 'published')],
        ];
    }

    public function messages(): array
    {
        return ['published_at.required' => 'Choose when this notice should become public.'];
    }
}
