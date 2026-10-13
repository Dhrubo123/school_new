<?php

namespace App\Http\Requests\Admin;

use App\Models\SchoolSetting;
use Illuminate\Foundation\Http\FormRequest;

class SchoolSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SchoolSetting::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:150'],
            'short_name' => ['required', 'string', 'max:80'],
            'eiin' => ['nullable', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:160'],
            'address' => ['required', 'string', 'max:500'],
            'office_hours' => ['nullable', 'string', 'max:200'],
            'map_embed_url' => ['nullable', 'url', 'max:1000'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
