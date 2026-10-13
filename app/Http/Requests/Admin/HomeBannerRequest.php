<?php

namespace App\Http\Requests\Admin;

use App\Models\HomeBanner;
use Illuminate\Foundation\Http\FormRequest;

class HomeBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $banner = $this->route('banner');

        return $this->user()?->can($banner ? 'update' : 'create', $banner ?? HomeBanner::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:1500'],
            'image' => [$this->route('banner') ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'primary_button_label' => ['nullable', 'string', 'max:80', 'required_with:primary_button_url'],
            'primary_button_url' => ['nullable', 'string', 'max:500', 'required_with:primary_button_label'],
            'secondary_button_label' => ['nullable', 'string', 'max:80', 'required_with:secondary_button_url'],
            'secondary_button_url' => ['nullable', 'string', 'max:500', 'required_with:secondary_button_label'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }
}
