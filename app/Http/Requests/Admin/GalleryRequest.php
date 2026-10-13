<?php

namespace App\Http\Requests\Admin;

use App\Models\Gallery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $gallery = $this->route('gallery');

        return $this->user()?->can($gallery ? 'update' : 'create', $gallery ?? Gallery::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_published' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date', Rule::requiredIf($this->boolean('is_published'))],
        ];
    }
}
