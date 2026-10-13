<?php

namespace App\Http\Requests\Admin;

use App\Models\SchoolEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('event') ? 'update' : 'create', $this->route('event') ?? SchoolEvent::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:20000'],
            'venue' => ['required', 'string', 'max:180'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date', Rule::requiredIf($this->input('status') === 'published')],
        ];
    }
}
